<?php

namespace App\Http\Controllers;

use App\Services\HotspotService;
use Illuminate\Http\Request;

class HotspotController extends Controller
{
    public function __construct(
        protected HotspotService $hotspotService
    ) {
    }

    public function index(Request $request)
    {
        $this->hotspotService->initializeSession(
            $request->query('mac', ''),
            $request->query('ip', ''),
            $request->query('link_login', ''),
            urldecode(
                $request->query(
                    'link_orig',
                    $request->query('link-orig', '')
                )
            ),
            $request->query('router_name', '')
        );

        return view('hotspot.index');
    }

    public function sendOtp(Request $request)
    {
        return $this->hotspotService->sendOtp($request);
    }

    public function showOtp()
    {
        $result = $this->hotspotService->showOtp();

        if (!$result['success']) {
            return redirect()->route('hotspot.index');
        }

        return view('hotspot.otp');
    }

    public function verifyOtp(Request $request)
    {
        return $this->hotspotService->verifyOtp($request);
    }

    public function resendOtp(Request $request)
    {
        return $this->hotspotService->resendOtp($request);
    }

    public function success()
    {
        $result = $this->hotspotService->success();

        if (!$result['success']) {
            return redirect()->route('hotspot.index');
        }

        return view('hotspot.auto-login', [
            'username' => $result['username'],
            'password' => $result['password'],
            'linkLogin' => $result['linkLogin'],
            'linkOrig' => $result['linkOrig'],
        ]);
    }
}