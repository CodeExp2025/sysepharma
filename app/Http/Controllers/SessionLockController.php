<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SessionLockController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const SESSION_KEY  = 'lock_screen_attempts';

    /**
     * Verify the user's password from the idle lock screen.
     * Tracks failed attempts in session; forces logout after MAX_ATTEMPTS failures.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        $user     = $request->user();
        $attempts = (int) $request->session()->get(self::SESSION_KEY, 0);

        // Already exceeded — force logout
        if ($attempts >= self::MAX_ATTEMPTS) {
            return $this->forceLogout($request);
        }

        if (Hash::check($request->input('password'), $user->password)) {
            $request->session()->forget(self::SESSION_KEY);
            return response()->json(['success' => true]);
        }

        $attempts++;
        $request->session()->put(self::SESSION_KEY, $attempts);
        $remaining = self::MAX_ATTEMPTS - $attempts;

        if ($remaining <= 0) {
            return $this->forceLogout($request);
        }

        return response()->json([
            'success'   => false,
            'remaining' => $remaining,
            'message'   => "Mot de passe incorrect. {$remaining} tentative(s) restante(s).",
        ], 401);
    }

    private function forceLogout(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['logout' => true], 401);
    }
}
