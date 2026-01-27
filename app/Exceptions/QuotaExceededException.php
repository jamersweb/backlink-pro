<?php

namespace App\Exceptions;

use Exception;
use Carbon\Carbon;

class QuotaExceededException extends Exception
{
    protected $quotaKey;
    protected $limit;
    protected $used;
    protected $resetDate;

    public function __construct(string $quotaKey, int $limit, int $used, ?Carbon $resetDate = null, string $message = '')
    {
        $this->quotaKey = $quotaKey;
        $this->limit = $limit;
        $this->used = $used;
        $this->resetDate = $resetDate;

        if (empty($message)) {
            $resetText = $resetDate ? " Resets on {$resetDate->format('Y-m-d')}." : '';
            $message = "You reached your limit for {$quotaKey} ({$used}/{$limit}).{$resetText}";
        }

        parent::__construct($message);
    }

    public function getQuotaKey(): string
    {
        return $this->quotaKey;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getUsed(): int
    {
        return $this->used;
    }

    public function getResetDate(): ?Carbon
    {
        return $this->resetDate;
    }

    public function render($request)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'quota_key' => $this->quotaKey,
                'limit' => $this->limit,
                'used' => $this->used,
                'reset_date' => $this->resetDate?->format('Y-m-d'),
            ], 403);
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->with('quota_exceeded', true);
    }
}


