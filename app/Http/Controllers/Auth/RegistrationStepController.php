<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegistrationStepController extends Controller
{
    public function showCompany()
    {
        return view('pages.auth.register-company');
    }

    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['required', 'string', 'max:255'],
            'company_address' => ['required', 'string', 'max:255'],
        ]);

        session($validated);

        return redirect()->route('register.branch');
    }

    public function showBranch()
    {
        if (!session('company_name')) {
            return redirect()->route('register.company');
        }

        return view('pages.auth.register-branch');
    }

    public function storeBranch(Request $request)
    {
        if (!session('company_name')) {
            return redirect()->route('register.company');
        }

        $validated = $request->validate([
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_phone' => ['required', 'string', 'max:255'],
            'branch_address' => ['required', 'string', 'max:255'],
        ]);

        session($validated);

        return redirect()->route('register.admin');
    }

    public function showAdmin()
    {
        if (!session('company_name') || !session('branch_name')) {
            return redirect()->route('register.company');
        }

        return view('pages.auth.register-admin');
    }
}
