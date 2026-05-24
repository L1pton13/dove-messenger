<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'], 
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'tag' => ['required', 'string', 'max:255', 'unique:users,tag,' . $user->id], // не забываем про твой тег
            'avatar' => ['nullable', 'image', 'max:2048'], 
        ]);

        $user->fill($request->safe()->except(['avatar']));

        if ($request->email !== $user->getOriginal('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            // Удаляем физический файл с диска
            Storage::disk('public')->delete($user->avatar);
            
            // Сбрасываем поле в базе данных в null
            $user->avatar = null;
            $user->save();
        }

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
