<?php

namespace Softpro\Core\Http\Controllers\Auth;

use Softpro\Core\Http\Controllers\Controller;
use Softpro\Core\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Inertia\Inertia;

class ApplicantAuthController extends Controller
{
    public function showRegister()
    {
        return Inertia::render('Auth/ApplicantRegister');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:applicants',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $applicant = Applicant::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($applicant));

        Auth::guard('applicant')->login($applicant);

        return redirect()->route('applicant.dashboard');
    }

    public function showLogin()
    {
        return Inertia::render('Auth/ApplicantLogin');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (Auth::guard('applicant')->attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('applicant.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('applicant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('applicant.login');
    }
}
