<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GmailOAuthController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class GoogleUnifiedCallbackController extends Controller
{
    public function handle(
        Request $request,
        GmailOAuthController $gmailOAuthController,
        SocialAuthController $socialAuthController
    ): RedirectResponse {
        $flow = (string) $request->session()->pull('google_oauth_flow', '');

        if ($flow === 'gmail_connect') {
            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please log in before connecting Gmail.');
            }

            return $gmailOAuthController->callback($request);
        }

        return $socialAuthController->callback($request, 'google');
    }
}
