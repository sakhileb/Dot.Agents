<?php

namespace App\Actions\Fortify;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]), function (User $user) {
                $this->createTeam($user);
                $this->createOrganization($user);
            });
        });
    }

    /**
     * Create a personal Jetstream team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }

    /**
     * Create a personal Organization for the user and attach them as owner.
     */
    protected function createOrganization(User $user): void
    {
        $baseName = explode(' ', $user->name, 2)[0]."'s Organization";
        $baseSlug = Str::slug($baseName);

        // Ensure unique slug
        $slug = $baseSlug;
        $count = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count++;
        }

        $org = Organization::create([
            'name' => $baseName,
            'slug' => $slug,
            'owner_id' => $user->id,
            'plan' => 'starter',
            'status' => 'trial',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Attach the user as the owner member (primary)
        $org->users()->attach($user->id, [
            'role' => 'owner',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
    }
}
