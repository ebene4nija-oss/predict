<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Create (or promote) an administrator from the console.
 *
 * Until this existed the only account with the admin role came from the demo
 * seeder, on a published email address with the password "password" — so the
 * only documented way to get an admin on a live box was to run the seeder and
 * hand an attacker the keys. This is the supported path instead: it writes one
 * user and nothing else.
 *
 * The password is asked for interactively by default. Passing --password puts
 * the live admin credential into shell history and into the process list of
 * every other user on the machine, so it is accepted for automated provisioning
 * but warned about.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
                            {--name= : Display name}
                            {--email= : Login email address}
                            {--password= : Password; omit this to be prompted (see warning)}
                            {--promote : Raise an existing account to admin instead of failing}';

    protected $description = 'Create an administrator account, or promote an existing user to admin';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');

        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->option('promote')) {
            $this->error("{$email} already exists. Re-run with --promote to make this account an admin.");

            return self::FAILURE;
        }

        $password = $this->resolvePassword(promoting: (bool) $existing);

        // A promotion with no new password keeps the one the account already
        // has; null here means "leave it alone", not "blank it".
        if ($password === false) {
            return self::FAILURE;
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // Uniqueness is exactly what --promote steps around, so it only
            // applies when this command is creating the account.
            'email' => array_merge(
                ['required', 'string', 'email', 'max:255'],
                $existing ? [] : ['unique:users,email'],
            ),
        ];

        // Stricter than the registration form on purpose. A guessed subscriber
        // password costs one subscription; a guessed admin password is the
        // whole site, including every gateway key held in the settings table.
        // Deliberately no uncompromised() check — that calls a remote API, and
        // a deploy-time command must not fail because the box has no egress.
        if ($password !== null) {
            $rules['password'] = ['required', Password::min(12)->letters()->numbers()->symbols()];
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            $rules,
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($existing) {
            $existing->role = 'admin';

            if ($password !== null) {
                $existing->password = $password;
            }

            $existing->save();

            $this->info("Promoted {$existing->email} to admin.");

            return self::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
        ]);

        // Whoever ran this already has shell access to the server, so a mailed
        // confirmation proves nothing they have not already demonstrated — and
        // the first admin is normally created before SMTP is configured, which
        // would otherwise leave them on the verification notice with no way to
        // receive the mail. Set after create because email_verified_at is
        // deliberately not mass-assignable.
        $user->markEmailAsVerified();

        $this->info("Created admin {$user->email}.");

        return self::SUCCESS;
    }

    /**
     * The password to set, null to keep the existing one, or false to abort.
     *
     * @return string|null|false
     */
    protected function resolvePassword(bool $promoting): string|null|false
    {
        if ($password = $this->option('password')) {
            $this->warn('Passing --password leaves the credential in your shell history; rotate it if this is a shared machine.');

            return $password;
        }

        // No option and nothing to prompt with: fail loudly rather than invent
        // a password and print it, which tends to end up in a CI log.
        if (! $this->input->isInteractive()) {
            if ($promoting) {
                return null;
            }

            $this->error('No --password given and the console is not interactive; cannot create an account without one.');

            return false;
        }

        if ($promoting && ! $this->confirm('Set a new password for this account?', false)) {
            return null;
        }

        $password = $this->secret('Password');

        if ($password !== $this->secret('Confirm password')) {
            $this->error('The passwords do not match.');

            return false;
        }

        return $password;
    }
}
