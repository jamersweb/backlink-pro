<?php

namespace App\Services\System;

use App\Models\SystemActivityLog;
use App\Models\JobFailure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Log activity
     */
    public function log(
        string $feature,
        string $action,
        string $status,
        string $message,
        ?int $userId = null,
        ?int $domainId = null,
        array $context = []
    ): SystemActivityLog {
        $userId = $userId ?? Auth::id();

        return SystemActivityLog::create([
            'user_id' => $userId,
            'domain_id' => $domainId,
            'feature' => $feature,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'context_json' => $context,
        ]);
    }

    /**
     * Log info
     */
    public function info(
        string $feature,
        string $action,
        string $message,
        ?int $userId = null,
        ?int $domainId = null,
        array $context = []
    ): SystemActivityLog {
        return $this->log($feature, $action, SystemActivityLog::STATUS_INFO, $message, $userId, $domainId, $context);
    }

    /**
     * Log success
     */
    public function success(
        string $feature,
        string $action,
        string $message,
        ?int $userId = null,
        ?int $domainId = null,
        array $context = []
    ): SystemActivityLog {
        return $this->log($feature, $action, SystemActivityLog::STATUS_SUCCESS, $message, $userId, $domainId, $context);
    }

    /**
     * Log warning
     */
    public function warning(
        string $feature,
        string $action,
        string $message,
        ?int $userId = null,
        ?int $domainId = null,
        array $context = []
    ): SystemActivityLog {
        return $this->log($feature, $action, SystemActivityLog::STATUS_WARNING, $message, $userId, $domainId, $context);
    }

    /**
     * Log error
     */
    public function error(
        string $feature,
        string $action,
        string $message,
        ?int $userId = null,
        ?int $domainId = null,
        array $context = []
    ): SystemActivityLog {
        return $this->log($feature, $action, SystemActivityLog::STATUS_ERROR, $message, $userId, $domainId, $context);
    }

    /**
     * Log job failure
     */
    public function logJobFailure(
        string $feature,
        string $jobName,
        \Exception $exception,
        ?int $domainId = null,
        ?int $userId = null,
        ?string $runRef = null,
        array $context = []
    ): JobFailure {
        $message = $exception->getMessage();
        if (strlen($message) > 800) {
            $message = substr($message, 0, 797) . '...';
        }

        $failure = JobFailure::create([
            'feature' => $feature,
            'job_name' => $jobName,
            'domain_id' => $domainId,
            'user_id' => $userId,
            'run_ref' => $runRef,
            'exception_class' => get_class($exception),
            'exception_message' => $message,
            'failed_at' => now(),
            'context_json' => array_merge($context, [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]),
        ]);

        // Also log as error activity
        $this->error(
            $feature,
            'failed',
            "Job {$jobName} failed: " . $message,
            $userId,
            $domainId,
            array_merge($context, ['run_ref' => $runRef, 'exception_class' => get_class($exception)])
        );

        return $failure;
    }

    /**
     * Generate run reference
     */
    public static function runRef(string $feature, int $id): string
    {
        return "{$feature}:{$id}";
    }

    /**
     * Get fix hint for exception
     */
    public static function getFixHint(\Exception $exception): ?string
    {
        $message = strtolower($exception->getMessage());
        $class = get_class($exception);

        // Google API errors
        if (strpos($class, 'Google') !== false || strpos($message, '401') !== false || strpos($message, 'unauthorized') !== false) {
            return 'Reconnect Google account';
        }

        // Rate limiting
        if (strpos($message, '429') !== false || strpos($message, 'rate limit') !== false) {
            return 'Rate limited, retry later';
        }

        // Timeout
        if (strpos($message, 'timeout') !== false || strpos($message, 'timed out') !== false) {
            return 'Increase timeout or reduce crawl limit';
        }

        // Connection errors
        if (strpos($message, 'connection') !== false || strpos($message, 'network') !== false) {
            return 'Check network connection and retry';
        }

        return null;
    }
}


