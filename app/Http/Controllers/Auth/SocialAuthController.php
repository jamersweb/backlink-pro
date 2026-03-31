<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['google', 'apple', 'github', 'microsoft', 'facebook'];

    private const PROVIDER_ID_COLUMNS = [
        'google' => 'google_id',
        'apple' => 'apple_id',
        'github' => 'github_id',
        'microsoft' => 'microsoft_id',
        'facebook' => 'facebook_id',
    ];

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (!$this->isSupportedProvider($provider)) {
            abort(404);
        }

        if (!$this->isProviderConfigured($provider)) {
            return back()->with('error', ucfirst($provider) . ' login is not configured yet.');
        }

        if (!$this->isProviderAvailable($provider)) {
            return back()->with('error', ucfirst($provider) . ' login driver is missing on server.');
        }

        if ($safeIntended = $this->resolveIntendedFromRequest($request)) {
            $request->session()->put('url.intended', $safeIntended);
        }

        if ($provider === 'google') {
            $request->session()->put('google_oauth_flow', 'social_login');
        }

        try {
            $driver = Socialite::driver($provider);
            $this->applyProviderScopes($driver, $provider);

            return $driver->redirect();
        } catch (\Throwable $e) {
            Log::warning('Social login redirect failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', ucfirst($provider) . ' login is temporarily unavailable. Please try again.');
        }
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (!$this->isSupportedProvider($provider)) {
            abort(404);
        }

        if (!$this->isProviderConfigured($provider)) {
            return redirect()->route('login')->with('error', ucfirst($provider) . ' login is not configured yet.');
        }

        if (!$this->isProviderAvailable($provider)) {
            return redirect()->route('login')->with('error', ucfirst($provider) . ' login driver is missing on server.');
        }

        $hadIntended = $request->session()->has('url.intended');

        try {
            $driver = Socialite::driver($provider);
            $this->applyProviderScopes($driver, $provider);
            $socialUser = $driver->user();
        } catch (\Throwable $e) {
            Log::warning('Social login callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', ucfirst($provider) . ' login failed. Please try again.');
        }

        $providerUserId = (string) ($socialUser->getId() ?? '');
        if ($providerUserId === '') {
            return redirect()->route('login')->with('error', 'Unable to identify your social account. Please try again.');
        }

        $providerColumn = self::PROVIDER_ID_COLUMNS[$provider];
        $providerColumnExists = Schema::hasColumn('users', $providerColumn);
        $avatarColumnExists = Schema::hasColumn('users', 'avatar_url');

        $email = $this->normalizeEmail($socialUser->getEmail());
        $name = $socialUser->getName() ?: $socialUser->getNickname();
        $avatar = $socialUser->getAvatar();
        $emailVerified = $this->isEmailVerifiedFromProvider($provider, $socialUser);

        // Safety-net: if provider column is missing, skip provider lookup and use email fallback.
        $user = null;
        if ($providerColumnExists) {
            $user = User::where($providerColumn, $providerUserId)->first();
        }

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            $updates = [];

            if ($providerColumnExists) {
                $updates[$providerColumn] = $providerUserId;
            }

            if ($avatarColumnExists) {
                $updates['avatar_url'] = $avatar ?: $user->avatar_url;
            }

            if (blank($user->name) && !blank($name)) {
                $updates['name'] = $name;
            }

            if (!$user->email_verified_at && $emailVerified) {
                $updates['email_verified_at'] = now();
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }
        } else {
            if (!$email) {
                return redirect()->route('login')->with('error', 'This provider did not return an email. Please use email login first.');
            }

            $createPayload = [
                'name' => $name ?: Str::before($email, '@') ?: 'User',
                'email' => $email,
                'password' => Str::random(40),
                'email_verified_at' => $emailVerified ? now() : null,
            ];

            if ($providerColumnExists) {
                $createPayload[$providerColumn] = $providerUserId;
            }

            if ($avatarColumnExists) {
                $createPayload['avatar_url'] = $avatar;
            }

            $user = User::create($createPayload);
            $this->assignDefaultRole($user);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->startFreeTrialIfEligible();

        if ($user->hasRole('admin')) {
            return redirect()->to('/admin/dashboard');
        }

        $fallback = $hadIntended ? '/seo-audit-report' : '/dashboard';

        return redirect()->intended($fallback);
    }

    private function isSupportedProvider(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS, true);
    }

    private function isProviderConfigured(string $provider): bool
    {
        $clientId = (string) config("services.{$provider}.client_id", '');
        $clientSecret = (string) config("services.{$provider}.client_secret", '');

        return $clientId !== '' && $clientSecret !== '';
    }

    private function isProviderAvailable(string $provider): bool
    {
        return match ($provider) {
            'microsoft' => class_exists(\SocialiteProviders\Microsoft\Provider::class),
            'apple' => class_exists(\SocialiteProviders\Apple\Provider::class),
            default => true,
        };
    }

    private function resolveIntendedFromRequest(Request $request): ?string
    {
        foreach (['intended', 'redirect', 'next'] as $key) {
            $value = $this->sanitizeIntendedPath($request->query($key));
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    private function sanitizeIntendedPath(mixed $value): ?string
    {
        $path = trim((string) $value);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, '//')) {
            return null;
        }

        return Str::startsWith($path, '/') ? $path : null;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = strtolower(trim((string) $email));

        return $normalized !== '' ? $normalized : null;
    }

    private function applyProviderScopes(object $driver, string $provider): void
    {
        if ($provider === 'google') {
            $driver->scopes(['openid', 'profile', 'email']);
        }

        if ($provider === 'microsoft') {
            $driver->scopes(['openid', 'profile', 'email', 'User.Read']);
        }

        if ($provider === 'facebook') {
            $driver->scopes(['email', 'public_profile']);
        }
    }

    private function isEmailVerifiedFromProvider(string $provider, object $socialUser): bool
    {
        $raw = method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : [];
        if (!is_array($raw)) {
            $raw = [];
        }

        if ($provider === 'google') {
            return (bool) ($raw['email_verified'] ?? false);
        }

        if ($provider === 'apple') {
            if (array_key_exists('email_verified', $raw)) {
                return filter_var($raw['email_verified'], FILTER_VALIDATE_BOOLEAN);
            }

            return true;
        }

        if ($provider === 'facebook') {
            return !blank($socialUser->getEmail());
        }

        return (bool) ($raw['verified_email'] ?? $raw['email_verified'] ?? false);
    }

    private function assignDefaultRole(User $user): void
    {
        try {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => 'user',
                'guard_name' => 'web',
            ]);
            $user->assignRole('user');
        } catch (\Throwable $e) {
            Log::warning('Failed to assign user role during social auth', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


