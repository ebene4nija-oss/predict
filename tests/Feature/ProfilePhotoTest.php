<?php

namespace Tests\Feature;

use App\Models\Expert;
use App\Models\ExpertPick;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_profile_picture(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('admin-avatar.jpg', 400, 400);

        $response = $this->actingAs($admin)
            ->post(route('account.avatar'), [
                'avatar' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertNotNull($admin->avatar);
        $this->assertTrue(Storage::disk('public')->exists($admin->avatar));
        $this->assertStringContainsString('storage/avatars/', $admin->avatarUrl());
    }

    public function test_expert_can_upload_profile_picture_and_syncs_with_expert_record(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'expert',
            'email_verified_at' => now(),
        ]);

        $expert = Expert::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'bio' => 'Senior Football Analyst',
        ]);

        $file = UploadedFile::fake()->image('expert-photo.png', 400, 400);

        $response = $this->actingAs($user)
            ->post(route('account.avatar'), [
                'avatar' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $expert->refresh();

        $this->assertNotNull($user->avatar);
        $this->assertEquals($user->avatar, $expert->photo_path);
        $this->assertStringContainsString('storage/avatars/', $expert->photoUrl());
    }

    public function test_regular_free_and_subscriber_users_cannot_upload_profile_picture(): void
    {
        Storage::fake('public');

        $freeUser = User::factory()->create([
            'role' => 'free',
            'email_verified_at' => now(),
        ]);

        $subscriberUser = User::factory()->create([
            'role' => 'subscriber',
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('fake.png', 400, 400);

        // Free user attempted upload
        $response1 = $this->actingAs($freeUser)
            ->post(route('account.avatar'), [
                'avatar' => $file,
            ]);
        $response1->assertForbidden();

        // Subscriber user attempted upload
        $response2 = $this->actingAs($subscriberUser)
            ->post(route('account.avatar'), [
                'avatar' => $file,
            ]);
        $response2->assertForbidden();
    }

    public function test_profile_photo_upload_form_is_hidden_from_regular_users(): void
    {
        $subscriberUser = User::factory()->create([
            'role' => 'subscriber',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($subscriberUser)
            ->get(route('account'))
            ->assertOk()
            ->assertDontSee('Profile Picture & Analyst Photo')
            ->assertDontSee('Save Profile Picture');
    }

    public function test_profile_photo_upload_form_is_visible_for_admins_and_experts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Profile Picture &amp; Analyst Photo', false)
            ->assertSee('Save Profile Picture');
    }

    public function test_expert_profile_photo_is_visible_on_picks_feed_and_leaderboard(): void
    {
        $expert = Expert::create([
            'name' => 'Tactical Master',
            'bio' => 'Data-driven match strategist',
            'photo_path' => 'https://example.com/expert-photo.jpg',
        ]);

        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addHours(5),
            'status' => 'upcoming',
        ]);

        ExpertPick::create([
            'expert_id' => $expert->id,
            'match_id' => $match->id,
            'market' => 'win',
            'pick' => 'Home Win',
            'confidence' => 0.85,
            'rationale' => 'Arsenal has superior press resistance.',
        ]);

        // Leaderboard check
        $this->get(route('expert.leaderboard'))
            ->assertOk()
            ->assertSee('Tactical Master')
            ->assertSee('https://example.com/expert-photo.jpg');

        // Expert Picks check
        $this->get(route('expert.picks'))
            ->assertOk()
            ->assertSee('Tactical Master')
            ->assertSee('https://example.com/expert-photo.jpg');
    }

    public function test_admin_can_remove_profile_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
            'avatar' => 'avatars/existing.jpg',
            'email_verified_at' => now(),
        ]);
        Storage::disk('public')->put('avatars/existing.jpg', 'fake-image-content');

        $response = $this->actingAs($admin)
            ->post(route('account.avatar'), [
                'remove_avatar' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertNull($admin->avatar);
        $this->assertFalse(Storage::disk('public')->exists('avatars/existing.jpg'));
    }
}
