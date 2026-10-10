<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => $data['role'],
                'country'  => $data['country'] ?? null,
            ]);

            // Onboarding answers become the profile's starting location and
            // preferences, which in turn seed job matching and posting defaults.
            match ($user->role) {
                'employer'   => EmployerProfile::create([
                    'user_id'              => $user->id,
                    'company_name'         => $data['company_name'],
                    'headquarters_city'    => $data['city'] ?? null,
                    'headquarters_state'   => $data['state'] ?? null,
                    'headquarters_country' => $data['country'] ?? 'US',
                    'hiring_scopes'        => $data['hiring_scopes'] ?? null,
                ]),
                'job_seeker' => JobSeekerProfile::create([
                    'user_id'           => $user->id,
                    'current_city'      => $data['city'] ?? null,
                    'current_state'     => $data['state'] ?? null,
                    'current_country'   => $data['country'] ?? null,
                    'work_arrangements' => $data['work_arrangements'] ?? null,
                    'location_scopes'   => $data['location_scopes'] ?? null,
                ]),
                default => null,
            };

            $token = $user->createToken('auth_token')->plainTextToken;

            return ['user' => $user->load('employerProfile', 'jobSeekerProfile'), 'token' => $token];
        });
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended.'],
            ]);
        }

        $user->update(['last_seen_at' => now()]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user->load('employerProfile', 'jobSeekerProfile'), 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
