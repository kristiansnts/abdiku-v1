<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DemoSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class DemoController extends Controller
{
    public function ready()
    {
        if (! session()->has('demo_email')) {
            return redirect()->route('demo.show');
        }

        return view('demo.ready', [
            'email'    => session('demo_email'),
            'password' => session('demo_password'),
            'company'  => session('demo_company'),
        ]);
    }

    public function show()
    {
        return view('demo.start');
    }

    public function start(Request $request, DemoSessionService $service)
    {
        try {
            $result = $service->createSandbox();

            Auth::login($result['owner']);

            session()->flash('demo_email', $result['owner']->email);
            session()->flash('demo_password', $result['password']);
            session()->flash('demo_company', $result['company']->name);

            return redirect()->route('demo.ready');
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Gagal membuat sesi demo. Silakan coba lagi.']);
        }
    }
}
