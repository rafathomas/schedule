<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws \Throwable
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'company_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:10', 'max:32'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(static function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => $input['password'],
            ]);

            $baseSlug = Str::slug($input['company_name']);
            $slug = $baseSlug;

            while (Company::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.Str::lower(Str::random(5));
            }

            $company = Company::create([
                'uuid' => (string) Str::uuid(),
                'name' => $input['company_name'],
                'slug' => $slug,
                'phone' => $input['phone'],
                'whatsapp' => $input['phone'],
                'onboarding_steps' => [],
            ]);

            $company->users()->attach($user, [
                'role' => 'owner',
                'is_active' => true,
            ]);

            $user->forceFill(['current_company_id' => $company->getKey()])->save();

            return $user;
        });
    }
}
