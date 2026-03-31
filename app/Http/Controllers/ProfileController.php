<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Pharmacy;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load('pharmacies');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail'   => $user instanceof MustVerifyEmail,
            'status'            => session('status'),
            'userPharmacies'    => $user->pharmacies->map(fn($p) => ['id' => $p->id, 'name' => $p->name]),
            'activePharmacyId'  => $user->pharmacy_id,
        ]);
    }

    /**
     * Switch the user's active pharmacy.
     */
    public function switchPharmacy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        $user = $request->user();

        // Ensure the user belongs to that pharmacy
        if (! $user->pharmacies()->where('pharmacies.id', $validated['pharmacy_id'])->exists()) {
            abort(403, 'Vous n\'avez pas accès à cette pharmacie.');
        }

        $user->update(['pharmacy_id' => $validated['pharmacy_id']]);

        return Redirect::route('profile.edit')->with('status', 'Pharmacie active changée avec succès.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
