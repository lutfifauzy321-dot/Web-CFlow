<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('profile.index', ['u' => auth()->user(), 'tab' => 'profile', 'categories' => Category::options()]);
    }

    public function account(): View
    {
        return view('profile.account', ['u' => auth()->user(), 'tab' => 'profile', 'categories' => Category::options()]);
    }

    public function security(): View
    {
        return view('profile.security', ['u' => auth()->user(), 'tab' => 'profile', 'categories' => Category::options()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $avatarFile = null;
        try {
            if ($request->hasFile('avatar')) {
                $avatarFile = UploadService::storeImage($request->file('avatar'), 'avatars');
            }

            $oldAvatar = $user->avatar;
            $user->name = trim(strip_tags($validated['name']));
            $user->email = strtolower(trim($validated['email']));
            if ($avatarFile !== null) {
                $user->avatar = 'local:' . $avatarFile;
            }
            $user->save();
        } catch (\Throwable $exception) {
            if ($avatarFile !== null) {
                UploadService::removeAvatar('local:' . $avatarFile);
            }
            throw $exception;
        }

        if ($avatarFile !== null) {
            UploadService::removeAvatar($oldAvatar);
        }

        return redirect('/profile/account')->with('legacy_flash', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $hadPassword = (bool) $user->password_hash;
        $validated = $request->validate([
            'current_password' => [$user->password_hash ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($user->password_hash && !Hash::check($validated['current_password'], $user->password_hash)) {
            return redirect('/profile/security')->with('legacy_flash', 'Password saat ini salah.');
        }

        $user->password_hash = Hash::make($validated['password']);
        $user->save();

        return redirect('/profile/security')->with(
            'legacy_flash',
            $hadPassword ? 'Password berhasil diperbarui.' : 'Password berhasil dibuat untuk akun ini.'
        );
    }
}
