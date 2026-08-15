<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, LoginThrottle $throttle)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Locks the account under attack, not just the address attacking it.
        $throttle->assertNotLocked($request, $credentials['email']);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $throttle->clear($credentials['email']);
            $request->session()->regenerate();

            return redirect()->intended(route('home'))->with('success', 'Welcome back to Guaranteed Correct!');
        }

        $throttle->recordFailure($credentials['email']);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            // Password::defaults() applies the framework's configured policy
            // (length plus, in production, a compromised-password check)
            // rather than a bare eight characters.
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'free',
        ]);

        // Sends the verification email.
        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Account created! Please confirm your email address.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
