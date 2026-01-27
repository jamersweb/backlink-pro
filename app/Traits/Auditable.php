<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\SystemActivityLog;

/**
 * Auditable Trait
 * 
 * Automatically logs create, update, and delete events for models.
 * Tracks who made changes, what changed, and when.
 * 
 * Usage:
 * 1. Add `use Auditable;` to your model
 * 2. Optionally customize $auditExclude to hide sensitive fields
 * 3. Optionally set $auditCustomName for a friendly model name
 */
trait Auditable
{
    /**
     * Boot the trait
     */
    public static function bootAuditable(): void
    {
        // Log creation
        static::created(function (Model $model) {
            $model->logActivity('created', $model->getAuditableAttributes());
        });

        // Log updates
        static::updated(function (Model $model) {
            $original = $model->getOriginal();
            $changed = $model->getChanges();
            
            // Filter out excluded fields
            $excludeFields = $model->getAuditExcludeFields();
            $changes = [];
            
            foreach ($changed as $field => $newValue) {
                if (in_array($field, $excludeFields)) {
                    continue;
                }
                
                $changes[$field] = [
                    'old' => $original[$field] ?? null,
                    'new' => $newValue,
                ];
            }

            if (!empty($changes)) {
                $model->logActivity('updated', $changes);
            }
        });

        // Log soft deletes
        static::deleted(function (Model $model) {
            $action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_deleted'
                : 'deleted';
            
            $model->logActivity($action, [
                'id' => $model->getKey(),
                'deleted_data' => $model->getAuditableAttributes(),
            ]);
        });

        // Log restores (soft deletes)
        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                $model->logActivity('restored', ['id' => $model->getKey()]);
            });
        }
    }

    /**
     * Log an activity
     */
    public function logActivity(string $action, array $data = []): void
    {
        try {
            $user = Auth::user();
            $modelName = $this->getAuditModelName();

            SystemActivityLog::create([
                'user_id' => $user?->id,
                'feature' => $modelName,
                'action' => $action,
                'subject_type' => get_class($this),
                'subject_id' => $this->getKey(),
                'properties' => $data,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            // Don't let audit failures break the application
            Log::error('Audit logging failed', [
                'model' => get_class($this),
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get fields to exclude from audit (sensitive data)
     */
    protected function getAuditExcludeFields(): array
    {
        return property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['password', 'remember_token', 'api_token', 'secret', 'key'];
    }

    /**
     * Get a friendly model name for logs
     */
    protected function getAuditModelName(): string
    {
        return property_exists($this, 'auditCustomName')
            ? $this->auditCustomName
            : class_basename($this);
    }

    /**
     * Get attributes safe for audit logging
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->attributesToArray();
        $excludeFields = $this->getAuditExcludeFields();

        foreach ($excludeFields as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }

    /**
     * Manually log a custom activity on this model
     */
    public function logCustomActivity(string $action, array $data = [], string $status = 'success'): void
    {
        try {
            $user = Auth::user();
            $modelName = $this->getAuditModelName();

            SystemActivityLog::create([
                'user_id' => $user?->id,
                'feature' => $modelName,
                'action' => $action,
                'subject_type' => get_class($this),
                'subject_id' => $this->getKey(),
                'properties' => $data,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Custom audit logging failed', [
                'model' => get_class($this),
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
