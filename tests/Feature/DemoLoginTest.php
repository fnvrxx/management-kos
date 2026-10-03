<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(DemoUserSeeder::class);
    }

    public function test_demo_account_can_log_in_with_username_and_open_admin_panel(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Username atau email')
            ->assertSee('demo12345');

        Livewire::test(Login::class)
            ->set('data.login', 'demo')
            ->set('data.password', 'demo12345')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs(User::where('username', 'demo')->firstOrFail());

        config()->set('app.env', 'production');
        $this->get('/admin')->assertOk();
    }

    public function test_existing_email_login_still_works_and_wrong_password_is_rejected(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'username' => null,
        ]);

        Livewire::test(Login::class)
            ->set('data.login', 'admin@example.test')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($admin);

        Filament::auth()->logout();

        Livewire::test(Login::class)
            ->set('data.login', 'demo')
            ->set('data.password', 'salah')
            ->call('authenticate')
            ->assertHasErrors(['data.login']);
    }

    public function test_demo_seeder_does_not_duplicate_account(): void
    {
        $this->seed(DemoUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
    }
}
