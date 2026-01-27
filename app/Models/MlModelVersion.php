<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MlModelVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'version',
        'artifact_path',
        'metrics_json',
        'trained_on_rows',
        'feature_schema_hash',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'is_active' => 'boolean',
        'trained_on_rows' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get predictions
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(MlPrediction::class, 'model_version_id');
    }

    /**
     * Activate this version (deactivate others)
     */
    public function activate(): void
    {
        static::where('name', $this->name)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }
}
