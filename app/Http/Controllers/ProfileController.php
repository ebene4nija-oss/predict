<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Update or remove profile picture.
     * Allowed only for Admins and Experts.
     */
    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        if (! $user->canSetProfilePhoto()) {
            abort(403, 'Only administrators and verified experts are permitted to set profile pictures.');
        }

        $request->validate([
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'avatar_url' => 'nullable|url|max:500',
            'remove_avatar' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_avatar')) {
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->update(['avatar' => null]);

            if ($user->expert) {
                $user->expert->update(['photo_path' => null]);
            }

            return back()->with('success', 'Profile picture removed successfully.');
        }

        if ($request->hasFile('avatar')) {
            // Delete previous local file if any
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $path]);

            if ($user->expert) {
                $user->expert->update(['photo_path' => $path]);
            }

            return back()->with('success', 'Profile picture updated successfully!');
        }

        if ($request->filled('avatar_url')) {
            $url = $request->input('avatar_url');
            $user->update(['avatar' => $url]);

            if ($user->expert) {
                $user->expert->update(['photo_path' => $url]);
            }

            return back()->with('success', 'Profile picture URL saved successfully!');
        }

        return back()->with('error', 'Please choose an image file to upload.');
    }
}
