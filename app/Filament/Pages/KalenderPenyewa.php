<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PenyewaResource;
use App\Models\Penyewa;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

class KalenderPenyewa extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Kalender Penyewa';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Kalender Penyewa';

    protected static string $view = 'filament.pages.kalender-penyewa';

    public string $displayedMonth;

    public function mount(): void
    {
        $this->displayedMonth = CarbonImmutable::today()->startOfMonth()->toDateString();
    }

    public function previousMonth(): void
    {
        $this->displayedMonth = CarbonImmutable::parse($this->displayedMonth)
            ->subMonthNoOverflow()
            ->toDateString();
    }

    public function nextMonth(): void
    {
        $this->displayedMonth = CarbonImmutable::parse($this->displayedMonth)
            ->addMonthNoOverflow()
            ->toDateString();
    }

    public function currentMonth(): void
    {
        $this->displayedMonth = CarbonImmutable::today()->startOfMonth()->toDateString();
    }

    protected function getViewData(): array
    {
        $month = CarbonImmutable::parse($this->displayedMonth)->startOfMonth();
        $lastDay = $month->endOfMonth();
        $eventsByDate = [];

        $tenants = Penyewa::query()
            ->with('tempatKos')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>', CarbonImmutable::today()->toDateString());
            })
            ->where(function ($query) use ($month, $lastDay) {
                $query->whereBetween('start_date', [$month->toDateString(), $lastDay->toDateString()])
                    ->orWhereBetween('rencana_lama_kos', [$month->toDateString(), $lastDay->toDateString()]);
            })
            ->orderBy('nama_lengkap')
            ->get();

        foreach ($tenants as $tenant) {
            foreach (['start_date' => 'Mulai kos', 'rencana_lama_kos' => 'Rencana selesai'] as $field => $label) {
                $date = $tenant->{$field};

                if (! $date || $date->lt($month) || $date->gt($lastDay)) {
                    continue;
                }

                $eventsByDate[$date->toDateString()][] = [
                    'type' => $field === 'start_date' ? 'start' : 'end',
                    'label' => $label,
                    'name' => $tenant->nama_lengkap,
                    'room' => $tenant->tempatKos?->nomor_kamar,
                    'url' => PenyewaResource::getUrl('edit', ['record' => $tenant]),
                ];
            }
        }

        ksort($eventsByDate);

        $days = [];
        $gridStart = $month->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $lastDay->endOfWeek(CarbonImmutable::SUNDAY);

        for ($day = $gridStart; $day->lte($gridEnd); $day = $day->addDay()) {
            $days[] = [
                'date' => $day,
                'inMonth' => $day->month === $month->month,
                'events' => $eventsByDate[$day->toDateString()] ?? [],
            ];
        }

        return [
            'monthLabel' => $month->locale('id')->translatedFormat('F Y'),
            'days' => $days,
            'eventsByDate' => $eventsByDate,
            'today' => CarbonImmutable::today()->toDateString(),
        ];
    }
}
