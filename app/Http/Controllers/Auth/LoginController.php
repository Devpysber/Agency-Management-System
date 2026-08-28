<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Always issue a "remember me" cookie so a login persists across browser
     * restarts until the user explicitly logs out — separately for each panel
     * (admin/staff and client are different user records / sessions).
     */
    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt($this->credentials($request), true);
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * The dashboard isn't part of the Roles & Permissions matrix, so every
     * authenticated user lands there — the sidebar and route middleware
     * then restrict what each role can actually reach from there.
     * Client logins are a separate, fully-scoped panel and never see the
     * admin dashboard, so they're routed there directly.
     */
    protected function authenticated(Request $request, $user)
    {
        if ($user->role === 'client') {
            return redirect()->route('client.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
