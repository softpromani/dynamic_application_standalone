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

        // Create the applicant
        $applicant = Applicant::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // After creating the applicant, fire the Registered event which will send the verification email
        event(new Registered($applicant));

        // Log the applicant in using the applicant guard
        Auth::guard('applicant')->login($applicant);
        // Ensure subsequent requests use the applicant guard
        Auth::shouldUse('applicant');
        // If the applicant's email is not verified, redirect to verification notice with status flash
        if (method_exists($applicant, 'hasVerifiedEmail') && !$applicant->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('status', 'Verification link sent to your email address.');
        }

        // Email is verified; proceed to dashboard
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
            // Ensure the applicant guard is used for subsequent requests
            Auth::shouldUse('applicant');
            $request->session()->regenerate();
            $applicant = Auth::guard('applicant')->user();
            if (method_exists($applicant, 'hasVerifiedEmail') && !$applicant->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')
                    ->with('status', 'Please verify your email address.');
            }
            return redirect()->route('applicant.dashboard');
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
