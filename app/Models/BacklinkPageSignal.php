<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacklinkPageSignal extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'http_status',
        'content_type',
        'has_comment_form',
        'has_login_form',
        'has_register_link',
        'has_captcha',
        'is_cloudflare',
        'has_profile_fields',
        'has_forum_thread_ui',
        'has_editor_wysiwyg',
        'has_email_verify_hint',
        'outbound_links_count',
        'text_length',
        'signals_json',
        'created_at',
    ];

    protected $casts = [
        'has_comment_form' => 'boolean',
        'has_login_form' => 'boolean',
        'has_register_link' => 'boolean',
        'has_captcha' => 'boolean',
        'is_cloudflare' => 'boolean',
        'has_profile_fields' => 'boolean',
        'has_forum_thread_ui' => 'boolean',
        'has_editor_wysiwyg' => 'boolean',
        'has_email_verify_hint' => 'boolean',
        'signals_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the attempt
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(BacklinkAttempt::class, 'attempt_id');
    }
}
