<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function start(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $email = $request->email;
        $user = User::where('email', $email)->first();

        if ($user) {
            return redirect()->route('login', ['email' => $email]);
        }

        return redirect()->route('register', ['email' => $email]);
    }

    public function showLogin(Request $request)
    {
        $email = $request->query('email');
        return view('auth.login', compact('email'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            if (auth()->user()->isSuperAdmin()) {
                return redirect()->route('admin.super.dashboard');
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister(Request $request)
    {
        $email = $request->query('email');
        return view('auth.register', compact('email'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:255'],
            'address_street' => ['required', 'string', 'max:255'],
            'address_city' => ['required', 'string', 'max:255'],
            'address_state' => ['required', 'string', 'max:255'],
            'address_country' => ['required', 'string', 'max:255'],
            'address_zip' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_ADMIN, // The user registering their own account is an admin of their space
            'phone_number' => $request->phone_number,
            'company_name' => $request->company_name,
            'gstin' => $request->gstin,
            'address_street' => $request->address_street,
            'address_city' => $request->address_city,
            'address_state' => $request->address_state,
            'address_country' => $request->address_country,
            'address_zip' => $request->address_zip,
        ]);

        Auth::login($user);

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.super.dashboard');
        }

        return redirect(route('admin.dashboard', absolute: false));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
