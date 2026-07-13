<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_profile_page(): void
    {
        $this->get(EditProfile::getUrl())->assertRedirect();
    }

    #[Test]
    public function it_renders_the_profile_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EditProfile::class)->assertSuccessful();
    }

    #[Test]
    public function it_updates_the_users_name(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New Name', $user->refresh()->name);
    }

    #[Test]
    public function it_requires_a_name(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EditProfile::class)
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    }

    #[Test]
    public function it_updates_the_users_email_with_the_current_password(): void
    {
        $user = User::factory()->create(['email' => 'old@example.test', 'password' => Hash::make('password')]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => 'new@example.test',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('new@example.test', $user->refresh()->email);
    }

    #[Test]
    public function it_requires_a_valid_and_unique_email(): void
    {
        $user = User::factory()->create(['email' => 'old@example.test']);
        $this->actingAs($user);
        User::factory()->create(['email' => 'taken@example.test']);

        Livewire::test(EditProfile::class)
            ->fillForm(['email' => 'not-an-email', 'currentPassword' => 'password'])
            ->call('save')
            ->assertHasFormErrors(['email' => 'email']);

        Livewire::test(EditProfile::class)
            ->fillForm(['email' => 'taken@example.test', 'currentPassword' => 'password'])
            ->call('save')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    #[Test]
    public function it_updates_the_users_password_with_the_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'newpassword123',
                'passwordConfirmation' => 'newpassword123',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    #[Test]
    public function it_requires_the_correct_current_password_to_change_the_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'newpassword123',
                'passwordConfirmation' => 'newpassword123',
                'currentPassword' => 'wrong-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);
    }

    #[Test]
    public function it_requires_the_password_confirmation_to_match(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'newpassword123',
                'passwordConfirmation' => 'different-password',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The "save" action is rate-limited by component+method+IP via the file
        // cache driver, which isn't reset between tests like the database is.
        Cache::flush();
    }
}
