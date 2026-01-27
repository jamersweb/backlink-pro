<?php

namespace App\Jobs\Meta;

use App\Models\DomainMetaChange;
use App\Models\DomainConnector;
use App\Services\Meta\Connectors\ConnectorFactory;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishMetaChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $queue = 'meta';

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // Exponential backoff
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $changeId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $change = DomainMetaChange::findOrFail($this->changeId);
        $page = $change->page;
        $domain = $change->domain;
        
        // Get connector - prefer new DomainConnector, fallback to old DomainMetaConnector
        $connector = $domain->connector ?? $domain->metaConnector;

        if (!$connector) {
            $this->failChange($change, 'CONNECTOR_NOT_CONFIGURED', 'No connector configured for this domain');
            return;
        }

        // Handle new DomainConnector
        if ($connector instanceof DomainConnector) {
            $this->handleNewConnector($change, $page, $domain, $connector);
            return;
        }

        // Handle legacy DomainMetaConnector (fallback)
        if ($connector->status !== 'connected') {
            $this->failChange($change, 'CONNECTOR_NOT_CONFIGURED', 'Connector not connected');
            return;
        }

        // Legacy connector handling (keep existing code for backward compatibility)
        try {
            $metaConnector = \App\Services\Meta\Connectors\MetaConnectorFactory::make($connector->type);
            $meta = $change->meta_after_json;

            if ($connector->type === 'custom_js') {
                $page->update(['meta_published_json' => $meta]);
                $change->update(['status' => DomainMetaChange::STATUS_PUBLISHED]);
            } else {
                $result = $metaConnector->publishMeta($page, $meta, $connector);
                if ($result['ok']) {
                    $page->update(['meta_published_json' => $meta]);
                    $change->update(['status' => DomainMetaChange::STATUS_PUBLISHED]);
                } else {
                    $this->failChange($change, 'REMOTE_ERROR', $result['message'] ?? 'Publish failed');
                }
            }
        } catch (\Exception $e) {
            $this->handleException($change, $domain, $e);
        }
    }

    /**
     * Handle new DomainConnector
     */
    protected function handleNewConnector($change, $page, $domain, DomainConnector $connector): void
    {
        if ($connector->status !== DomainConnector::STATUS_CONNECTED) {
            $this->failChange($change, 'CONNECTOR_NOT_CONFIGURED', 'Connector not connected');
            return;
        }

        try {
            $metaConnector = ConnectorFactory::make($connector->type);
            $meta = $change->meta_after_json;

            // For custom_js, just store meta (publish is client-side)
            if ($connector->type === DomainConnector::TYPE_CUSTOM_JS) {
                $page->update(['meta_published_json' => $meta]);
                $change->update([
                    'status' => DomainMetaChange::STATUS_PUBLISHED,
                    'publish_attempts' => $change->publish_attempts + 1,
                ]);
                $this->logSuccess($domain, $change);
                return;
            }

            // Determine URL/handle to use for publishing
            $urlOrHandle = $page->remote_id ?? $page->handle ?? $page->url ?? $page->path;

            if (!$urlOrHandle) {
                $this->failChange($change, 'RESOURCE_NOT_FOUND', 'Could not determine resource identifier');
                return;
            }

            // Publish via connector
            $result = $metaConnector->publishMeta($urlOrHandle, $meta, $connector);

            $attempts = ($change->publish_attempts ?? 0) + 1;

            if ($result['ok']) {
                $page->update(['meta_published_json' => $meta]);
                $change->update([
                    'status' => DomainMetaChange::STATUS_PUBLISHED,
                    'publish_attempts' => $attempts,
                    'publish_error_code' => null,
                    'publish_error_message' => null,
                ]);
                $this->logSuccess($domain, $change);
            } else {
                $errorCode = $result['error_code'] ?? 'REMOTE_ERROR';
                $errorMessage = $result['message'] ?? 'Publish failed';

                // Handle specific error codes
                if ($errorCode === 'AUTH_FAILED') {
                    // Mark connector as error
                    $connector->update([
                        'status' => DomainConnector::STATUS_ERROR,
                        'last_error_code' => $errorCode,
                        'last_error_message' => $errorMessage,
                    ]);
                }

                // Determine if we should retry
                $shouldRetry = $this->shouldRetry($errorCode, $attempts);

                if ($shouldRetry && $attempts < $this->tries) {
                    // Retry the job
                    $change->update([
                        'publish_attempts' => $attempts,
                        'publish_error_code' => $errorCode,
                        'publish_error_message' => $errorMessage,
                    ]);
                    throw new \Exception("Retrying: {$errorMessage}");
                } else {
                    // Max attempts reached or non-retryable error
                    $this->failChange($change, $errorCode, $errorMessage, $attempts);
                }
            }
        } catch (\Exception $e) {
            $this->handleException($change, $domain, $e);
        }
    }

    /**
     * Fail the change with error code
     */
    protected function failChange($change, string $errorCode, string $errorMessage, int $attempts = null): void
    {
        $attempts = $attempts ?? ($change->publish_attempts ?? 0) + 1;
        
        $change->update([
            'status' => DomainMetaChange::STATUS_FAILED,
            'publish_attempts' => $attempts,
            'publish_error_code' => $errorCode,
            'publish_error_message' => $errorMessage,
            'error_message' => "[{$errorCode}] {$errorMessage}",
        ]);
    }

    /**
     * Determine if error should be retried
     */
    protected function shouldRetry(string $errorCode, int $attempts): bool
    {
        // Don't retry auth errors or validation errors
        if (in_array($errorCode, ['AUTH_FAILED', 'CONNECTOR_NOT_CONFIGURED', 'RESOURCE_NOT_FOUND', 'VALIDATION_ERROR'])) {
            return false;
        }

        // Retry rate limits, timeouts, and unknown errors
        return in_array($errorCode, ['RATE_LIMITED', 'TIMEOUT', 'UNKNOWN', 'REMOTE_ERROR']);
    }

    /**
     * Handle exception
     */
    protected function handleException($change, $domain, \Exception $e): void
    {
        $logger = app(ActivityLogger::class);
        $logger->logJobFailure(
            'meta',
            'PublishMetaChangeJob',
            $e,
            $domain->id,
            $change->user_id,
            ActivityLogger::runRef('meta', $this->changeId),
            ['change_id' => $this->changeId, 'page_id' => $change->page_id]
        );

        Log::error('Publish meta change failed', [
            'change_id' => $this->changeId,
            'error' => $e->getMessage(),
        ]);

        $attempts = ($change->publish_attempts ?? 0) + 1;
        
        // Only fail if we've exhausted retries
        if ($attempts >= $this->tries) {
            $this->failChange($change, 'UNKNOWN', $e->getMessage(), $attempts);
        } else {
            // Update attempts and rethrow for retry
            $change->update([
                'publish_attempts' => $attempts,
                'publish_error_code' => 'UNKNOWN',
                'publish_error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Log successful publish
     */
    protected function logSuccess($domain, $change): void
    {
        try {
            $logger = app(ActivityLogger::class);
            $logger->log('meta.publish.completed', [
                'domain_id' => $domain->id,
                'change_id' => $this->changeId,
                'page_id' => $change->page_id,
            ], $domain->id, $change->user_id);
        } catch (\Exception $e) {
            // Don't fail the job if logging fails
            Log::warning('Failed to log meta publish success', ['error' => $e->getMessage()]);
        }
    }
}
