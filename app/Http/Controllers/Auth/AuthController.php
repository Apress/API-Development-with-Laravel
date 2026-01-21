<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    //
    public function requestLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Find or create user
        $user = User::firstOrCreate(['email' => $request->email]);

        // Generate a magic link token
        $token     = Str::random(64);
        $expiresAt = Carbon::now()->addMinutes(15); // Link valid for 15 minutes

        // Store token in database
        MagicLink::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);

        // Generate login link
        $link = URL::temporarySignedRoute(
            'auth.login',
            $expiresAt,
            ['token' => $token]
        );

        // Send email
        Mail::to($user->email)->send(new \App\Mail\MagicLinkMail($link));
        //
        $masked_email = substr_replace($user->email, '****', 0, 3);
        return redirect()->route('sent_email_notifier', ['email' => $masked_email]);
    }

    public function login(Request $request, $token)
    {
        // Validate token
        $magicLink = MagicLink::where('token', $token)->first();

        if (! $magicLink || $magicLink->isExpired()) {
            return response()->json(['message' => 'Invalid or expired link.'], 401);
        }
        $user = $magicLink->user;
        // Log in the user
        auth()->login($user);
        return redirect()->route('home.pages.page', ['page' => 'transactions']);
    }
    public function logout()
    {
        auth()->logout();
        //return response()->json(['message' => 'Logged out successfully']);
        return redirect()->route('do.auth');

    }
}
