<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
        ...$this->profileRules(),
        'password' => $this->passwordRules(),
        'phone'           => ['nullable', 'string', 'max:255'],
    ])->validate();

    return DB::transaction(function () use ($input) {
        // Get company and branch data from session
        $company = Company::create([
            'name'    => session('company_name'),
            'email'   => session('company_email') ?? null,
            'phone'   => session('company_phone') ?? null,
            'address' => session('company_address') ?? null,
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name'       => session('branch_name'),
            'phone'      => session('branch_phone') ?? null,
            'address'    => session('branch_address') ?? null,
        ]);

        $user = User::create([
            'name'      => $input['name'],
            'email'     => $input['email'],
            'phone'     => $input['phone'] ?? null,
            'password'  => $input['password'],
            'branch_id' => $branch->id,
            'role_id'   => 1, // Default to admin role
        ]);

        // Clear registration session data
        session()->forget([
            'company_name', 'company_email', 'company_phone', 'company_address',
            'branch_name', 'branch_phone', 'branch_address'
        ]);

        return $user;
    });
}
}