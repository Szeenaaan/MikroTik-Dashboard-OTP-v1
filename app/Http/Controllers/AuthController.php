<?php

namespace App\Http\Controllers;

use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    public function __construct(
        private AuthenticationService $authenticationService
    ) {
    }

    public function showLogin()
    {
        return view('dashboard.login');
    }
    public function login(Request $request)
    {
        return $this->authenticationService->login(
            $request->username,
            $request->password
        );
    }

    public function logout(Request $request)
    {
        $this->authenticationService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
    public function activateApplication(Request $request)
    {
        $result = $this->authenticationService->activateApplication(
            $request->application_key,
            $request->userAgent(),
            $request->ip()
        );

        return response()->json($result, $result['statuscode']);
    }
    public function checkActivation(Request $request)
    {
        return response()->json(
            $this->authenticationService->checkActivation(
                $request->cookie('application_activation')
            )
        );
    }
    public function createUser(Request $request)
    {
        $result = $this->authenticationService->createUser(
            $request->username,
            $request->password
        );

        return response()->json($result, $result['statuscode']);
    }
    
}