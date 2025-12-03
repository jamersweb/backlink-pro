<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;
use App\Models\ConnectedAccount;
use App\Services\RateLimitingService;
use Illuminate\Support\Facades\Log;

class GmailService
{
    protected $client;
    protected $connectedAccount;

    public function __construct(?ConnectedAccount $connectedAccount = null)
    {
        $this->connectedAccount = $connectedAccount;
        $this->initializeClient();
    }

    /**
     * Initialize Google Client
     */
    protected function initializeClient()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('Google OAuth credentials are not configured. Please add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to your .env file.');
        }

        $this->client = new Google_Client();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->setScopes([
            'openid',
            'email',
            'profile',
            Google_Service_Gmail::GMAIL_READONLY,
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        if ($this->connectedAccount) {
            $this->setAccessToken($this->connectedAccount->access_token);
            $this->setRefreshToken($this->connectedAccount->refresh_token);
        }
    }

    /**
     * Set access token
     */
    public function setAccessToken(?string $token)
    {
        if ($token) {
            $this->client->setAccessToken($token);
        }
    }

    /**
     * Set refresh token
     */
    public function setRefreshToken(?string $token)
    {
        if ($token) {
            $this->client->refreshToken($token);
        }
    }

    /**
     * Get authorization URL
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for tokens
     */
    public function exchangeCodeForTokens(string $code): array
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($accessToken['error'])) {
                throw new \Exception('Error fetching access token: ' . $accessToken['error']);
            }

            return [
                'access_token' => $accessToken['access_token'],
                'refresh_token' => $accessToken['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($accessToken['expires_in'] ?? 3600),
            ];
        } catch (\Exception $e) {
            Log::error('Gmail OAuth token exchange failed', [
                'error' => $e->getMessage(),
                'code' => $code,
            ]);
            throw $e;
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(): array
    {
        if (!$this->connectedAccount || !$this->connectedAccount->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        try {
            $this->client->refreshToken($this->connectedAccount->refresh_token);
            $accessToken = $this->client->getAccessToken();

            if (isset($accessToken['error'])) {
                throw new \Exception('Error refreshing token: ' . $accessToken['error']);
            }

            // Update connected account
            $this->connectedAccount->update([
                'access_token' => $accessToken['access_token'],
                'refresh_token' => $accessToken['refresh_token'] ?? $this->connectedAccount->refresh_token,
                'expires_at' => now()->addSeconds($accessToken['expires_in'] ?? 3600),
            ]);

            return [
                'access_token' => $accessToken['access_token'],
                'refresh_token' => $accessToken['refresh_token'] ?? $this->connectedAccount->refresh_token,
                'expires_at' => $this->connectedAccount->expires_at,
            ];
        } catch (\Exception $e) {
            Log::error('Gmail token refresh failed', [
                'error' => $e->getMessage(),
                'account_id' => $this->connectedAccount->id,
            ]);
            
            // Mark account as expired
            $this->connectedAccount->update(['status' => ConnectedAccount::STATUS_EXPIRED]);
            throw $e;
        }
    }

    /**
     * Get Gmail service instance
     */
    public function getGmailService(): Google_Service_Gmail
    {
        // Ensure token is valid
        if ($this->connectedAccount && $this->connectedAccount->isExpiredOrExpiringSoon()) {
            $this->refreshAccessToken();
        }

        return new Google_Service_Gmail($this->client);
    }

    /**
     * Search for emails
     */
    public function searchEmails(string $query, int $maxResults = 10): array
    {
        // Check Gmail API rate limit
        $userId = $this->connectedAccount?->user_id ?? 0;
        if (!RateLimitingService::checkGmailApiRateLimit($userId, 250, 100)) {
            throw new \Exception('Gmail API rate limit exceeded. Please wait before making more requests.');
        }

        try {
            $service = $this->getGmailService();
            $messages = $service->users_messages->listUsersMessages('me', [
                'q' => $query,
                'maxResults' => $maxResults,
            ]);

            $emails = [];
            foreach ($messages->getMessages() as $message) {
                $msg = $service->users_messages->get('me', $message->getId());
                $emails[] = $this->parseEmail($msg);
            }

            return $emails;
        } catch (\Exception $e) {
            Log::error('Gmail email search failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            throw $e;
        }
    }

    /**
     * Get email by ID
     */
    public function getEmail(string $messageId): array
    {
        // Check Gmail API rate limit
        $userId = $this->connectedAccount?->user_id ?? 0;
        if (!RateLimitingService::checkGmailApiRateLimit($userId, 250, 100)) {
            throw new \Exception('Gmail API rate limit exceeded. Please wait before making more requests.');
        }

        try {
            $service = $this->getGmailService();
            $message = $service->users_messages->get('me', $messageId);
            return $this->parseEmail($message);
        } catch (\Exception $e) {
            Log::error('Gmail get email failed', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);
            throw $e;
        }
    }

    /**
     * Parse email message
     */
    protected function parseEmail($message): array
    {
        $payload = $message->getPayload();
        $headers = $payload->getHeaders();
        
        $email = [
            'id' => $message->getId(),
            'thread_id' => $message->getThreadId(),
            'snippet' => $message->getSnippet(),
            'subject' => $this->getHeader($headers, 'Subject'),
            'from' => $this->getHeader($headers, 'From'),
            'to' => $this->getHeader($headers, 'To'),
            'date' => $this->getHeader($headers, 'Date'),
            'body' => $this->getEmailBody($payload),
        ];

        return $email;
    }

    /**
     * Get header value
     */
    protected function getHeader($headers, string $name): ?string
    {
        foreach ($headers as $header) {
            if ($header->getName() === $name) {
                return $header->getValue();
            }
        }
        return null;
    }

    /**
     * Get email body
     */
    protected function getEmailBody($payload): string
    {
        $body = '';
        
        if ($payload->getBody() && $payload->getBody()->getData()) {
            $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
        } elseif ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->getMimeType() === 'text/plain' || $part->getMimeType() === 'text/html') {
                    if ($part->getBody() && $part->getBody()->getData()) {
                        $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                        break;
                    }
                }
            }
        }

        return $body;
    }

    /**
     * Extract verification links from email body
     */
    public function extractVerificationLinks(string $emailBody): array
    {
        $links = [];
        
        // Pattern for common verification link formats
        $patterns = [
            '/https?:\/\/[^\s<>"{}|\\^`\[\]]+verify[^\s<>"{}|\\^`\[\]]+/i',
            '/https?:\/\/[^\s<>"{}|\\^`\[\]]+confirm[^\s<>"{}|\\^`\[\]]+/i',
            '/https?:\/\/[^\s<>"{}|\\^`\[\]]+activate[^\s<>"{}|\\^`\[\]]+/i',
            '/https?:\/\/[^\s<>"{}|\\^`\[\]]+activation[^\s<>"{}|\\^`\[\]]+/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $emailBody, $matches)) {
                $links = array_merge($links, $matches[0]);
            }
        }

        // Also look for href attributes
        if (preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $emailBody, $matches)) {
            $links = array_merge($links, $matches[1]);
        }

        // Remove duplicates and filter
        $links = array_unique($links);
        $links = array_filter($links, function($link) {
            return filter_var($link, FILTER_VALIDATE_URL) !== false;
        });

        return array_values($links);
    }

    /**
     * Get user profile
     */
    public function getUserProfile(): array
    {
        try {
            $oauth2 = new \Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            
            return [
                'id' => $userInfo->getId(),
                'email' => $userInfo->getEmail(),
                'name' => $userInfo->getName(),
                'picture' => $userInfo->getPicture(),
            ];
        } catch (\Exception $e) {
            Log::error('Gmail get user profile failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

