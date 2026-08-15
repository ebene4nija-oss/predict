<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The only supported way to get an administrator onto a live installation.
 *
 * The demo seeder used to be the only one, which meant the documented route to
 * an admin account on production handed over a known password.
 */
class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected const STRONG = 'Str0ng!AdminPass';

    public function test_it_creates_an_admin_account(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Ops Lead',
            '--email' => 'ops@example.test',
            '--password' => self::STRONG,
        ])->assertExitCode(0);

        $admin = User::where('email', 'ops@example.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check(self::STRONG, $admin->password));
    }

    public function test_the_password_is_stored_hashed_not_in_the_clear(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Ops Lead',
            '--email' => 'ops@example.test',
            '--password' => self::STRONG,
        ])->assertExitCode(0);

        $this->assertNotSame(
            self::STRONG,
            User::where('email', 'ops@example.test')->value('password')
        );
    }

    /**
     * Without this the account cannot reach the admin screens until it has
     * received a mail — and the first admin is normally created before SMTP
     * exists, so there would be no way to receive it.
     */
    public function test_a_console_created_admin_is_already_verified(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Ops Lead',
            '--email' => 'ops@example.test',
            '--password' => self::STRONG,
        ])->assertExitCode(0);

        $this->assertNotNull(User::where('email', 'ops@example.test')->value('email_verified_at'));
    }

    public function test_it_refuses_a_weak_password(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Ops Lead',
            '--email' => 'ops@example.test',
            '--password' => 'password',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'ops@example.test']);
    }

    public function test_it_refuses_an_existing_email_without_promote(): void
    {
        User::factory()->create(['email' => 'someone@example.test', 'role' => 'free']);

        $this->artisan('admin:create', [
            '--name' => 'Ops Lead',
            '--email' => 'someone@example.test',
            '--password' => self::STRONG,
        ])->assertExitCode(1);

        $this->assertSame('free', User::where('email', 'someone@example.test')->value('role'));
    }

    public function test_promote_raises_an_existing_account_and_can_keep_its_password(): void
    {
        $user = User::factory()->create([
            'email' => 'someone@example.test',
            'role' => 'free',
            'password' => 'OldPassw0rd!x',
        ]);

        $this->artisan('admin:create', [
            '--name' => $user->name,
            '--email' => 'someone@example.test',
            '--promote' => true,
        ])
            ->expectsConfirmation('Set a new password for this account?', 'no')
            ->assertExitCode(0);

        $user->refresh();

        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('OldPassw0rd!x', $user->password));
    }

    public function test_promote_can_also_reset_the_password(): void
    {
        $user = User::factory()->create([
            'email' => 'someone@example.test',
            'role' => 'expert',
            'password' => 'OldPassw0rd!x',
        ]);

        $this->artisan('admin:create', [
            '--name' => $user->name,
            '--email' => 'someone@example.test',
            '--password' => self::STRONG,
            '--promote' => true,
        ])->assertExitCode(0);

        $user->refresh();

        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check(self::STRONG, $user->password));
    }

    public function test_it_prompts_for_the_details_when_no_options_are_given(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Ops Lead')
            ->expectsQuestion('Email', 'ops@example.test')
            ->expectsQuestion('Password', self::STRONG)
            ->expectsQuestion('Confirm password', self::STRONG)
            ->assertExitCode(0);

        $this->assertSame('admin', User::where('email', 'ops@example.test')->value('role'));
    }

    public function test_a_mistyped_password_confirmation_creates_nothing(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Ops Lead')
            ->expectsQuestion('Email', 'ops@example.test')
            ->expectsQuestion('Password', self::STRONG)
            ->expectsQuestion('Confirm password', self::STRONG.'typo')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'ops@example.test']);
    }

    /**
     * The seeder creates admin@guaranteedcorrectscoretips.com with the password "password", an
     * unearned subscription, and invented fixtures whose invented results feed
     * the public track record. `--force` skips db:seed's own production prompt,
     * so the refusal has to live in the seeder itself.
     */
    public function test_the_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->expectException(\RuntimeException::class);

            (new DatabaseSeeder)->run();
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_the_demo_seeder_still_runs_outside_production(): void
    {
        (new DatabaseSeeder)->run();

        $this->assertDatabaseHas('users', ['email' => 'admin@guaranteedcorrectscoretips.com']);
    }
}
