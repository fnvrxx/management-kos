<?php

namespace Tests\Feature;

use App\Filament\Pages\KalenderPenyewa;
use App\Models\Penyewa;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KalenderPenyewaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 30)->startOfDay());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_calendar_shows_active_tenant_dates_and_hides_checked_out_tenants(): void
    {
        $this->actingAs(User::factory()->create());

        Penyewa::create([
            'nama_lengkap' => 'Penyewa Aktif',
            'no_wa' => '0811111111',
            'start_date' => '2026-09-05',
            'rencana_lama_kos' => '2026-09-30',
        ]);

        Penyewa::create([
            'nama_lengkap' => 'Sudah Checkout',
            'no_wa' => '0822222222',
            'start_date' => '2026-09-06',
            'rencana_lama_kos' => '2026-09-29',
            'end_date' => '2026-09-20',
        ]);

        Penyewa::create([
            'nama_lengkap' => 'Checkout Hari Ini',
            'no_wa' => '0855555555',
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-30',
        ]);

        Penyewa::create([
            'nama_lengkap' => 'Checkout Mendatang',
            'no_wa' => '0833333333',
            'start_date' => '2026-09-07',
            'end_date' => '2026-10-01',
        ]);

        $this->get(KalenderPenyewa::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('September 2026')
            ->assertSee('Penyewa Aktif')
            ->assertSee('Checkout Mendatang')
            ->assertSee('Rencana selesai')
            ->assertSee('05/09/2026')
            ->assertSee('30/09/2026')
            ->assertDontSee('Sudah Checkout')
            ->assertDontSee('Checkout Hari Ini');
    }

    public function test_month_navigation_updates_calendar_events(): void
    {
        $user = User::factory()->create();

        Penyewa::create([
            'nama_lengkap' => 'Mulai Oktober',
            'no_wa' => '0844444444',
            'start_date' => '2026-10-03',
        ]);

        Livewire::actingAs($user)
            ->test(KalenderPenyewa::class)
            ->assertSee('September 2026')
            ->assertDontSee('Mulai Oktober')
            ->call('nextMonth')
            ->assertSee('Oktober 2026')
            ->assertSee('Mulai Oktober')
            ->call('previousMonth')
            ->assertSee('September 2026')
            ->assertDontSee('Mulai Oktober');
    }
}
