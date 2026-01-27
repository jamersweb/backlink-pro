<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlPrediction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'model_version_id',
        'user_id',
        'domain_id',
        'target_url',
        'features_hash',
        'predicted_action',
        'confidence',
        'probabilities_json',
        'created_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:4',
        'probabilities_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the model version
     */
    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(MlModelVersion::class, 'model_version_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
