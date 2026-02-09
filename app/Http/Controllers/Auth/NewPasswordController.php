<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
public function store(Request $request): RedirectResponse
{
    $request->validate([
        // 'token' => ['required'], // Matikan validasi token ini
        'email' => ['required', 'email', 'exists:users,email'],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    // Cari user berdasarkan email
    $user = User::where('email', $request->email)->first();

    // Langsung update password di database
    $user->forceFill([
        'password' => Hash::make($request->password),
        'remember_token' => Str::random(60),
    ])->save();

    event(new PasswordReset($user));

    // Arahkan kembali ke login dengan pesan sukses
    return redirect()->route('login')->with('status', 'Password berhasil diubah secara langsung!');
}

public function directUpdate(Request $request): \Illuminate\Http\RedirectResponse
{
    // 1. Validasi input
    $request->validate([
        'email' => ['required', 'email', 'exists:users,email'],
        'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
    ]);

    // 2. Cari user berdasarkan email
    $user = \App\Models\User::where('email', $request->email)->first();

    // 3. Update password langsung
    $user->forceFill([
        'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        'remember_token' => \Illuminate\Support\Str::random(60),
    ])->save();

    // 4. Redirect kembali ke login dengan pesan sukses
    return redirect()->route('login')->with('status', 'Password Anda telah berhasil diperbarui!');
}
}
