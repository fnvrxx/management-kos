<x-filament-panels::page>
    <style>
        .tenant-calendar .calendar-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
        .tenant-calendar .calendar-title { color: #111827; font-size: 1.25rem; font-weight: 600; }
        .tenant-calendar .calendar-description { margin-top: .25rem; color: #4b5563; font-size: .875rem; }
        .tenant-calendar .calendar-controls { display: flex; align-items: center; gap: .5rem; }
        .tenant-calendar .calendar-legend { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: 1.25rem; color: #374151; font-size: .875rem; }
        .tenant-calendar .legend-dot { display: inline-block; width: .625rem; height: .625rem; border-radius: 50%; }
        .tenant-calendar .legend-dot.start { background: #047857; }
        .tenant-calendar .legend-dot.end { background: #b45309; }
        .tenant-calendar .calendar-weekdays, .tenant-calendar .calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .tenant-calendar .calendar-weekdays { margin-top: 1.25rem; padding-bottom: .5rem; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-size: .875rem; font-weight: 500; text-align: center; }
        .tenant-calendar .calendar-grid { gap: 1px; overflow: hidden; border: 1px solid #e5e7eb; border-radius: .5rem; background: #e5e7eb; }
        .tenant-calendar .calendar-day { min-width: 0; min-height: 8rem; padding: .5rem; background: #fff; }
        .tenant-calendar .calendar-day.outside { background: #f9fafb; }
        .tenant-calendar .calendar-day-number { display: inline-flex; width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; border-radius: 50%; color: #111827; font-size: .875rem; }
        .tenant-calendar .calendar-day.outside .calendar-day-number { color: #9ca3af; }
        .tenant-calendar .calendar-day-number.today { background: #b45309; color: #fff; font-weight: 600; }
        .tenant-calendar .calendar-events { display: grid; gap: .25rem; margin-top: .5rem; }
        .tenant-calendar .calendar-event { display: block; padding: .25rem .5rem; border-radius: .375rem; font-size: .75rem; line-height: 1.4; text-decoration: none; overflow-wrap: anywhere; }
        .tenant-calendar .calendar-event:hover { text-decoration: underline; }
        .tenant-calendar .calendar-event:focus-visible { outline: 2px solid #92400e; outline-offset: 2px; }
        .tenant-calendar .calendar-event.start { background: #d1fae5; color: #064e3b; }
        .tenant-calendar .calendar-event.end { background: #fef3c7; color: #78350f; }
        .tenant-calendar .calendar-event-label, .tenant-calendar .calendar-event-name { display: block; }
        .tenant-calendar .calendar-event-label { font-weight: 600; }
        .tenant-calendar .calendar-mobile { display: none; }
        .tenant-calendar .calendar-empty { margin-top: 1rem; color: #4b5563; font-size: .875rem; }
        .tenant-calendar .calendar-note { margin-top: 1rem; color: #4b5563; font-size: .75rem; }
        .dark .tenant-calendar .calendar-title, .dark .tenant-calendar .calendar-mobile-date { color: #fff; }
        .dark .tenant-calendar .calendar-description, .dark .tenant-calendar .calendar-weekdays, .dark .tenant-calendar .calendar-empty, .dark .tenant-calendar .calendar-note { color: #d1d5db; }
        .dark .tenant-calendar .calendar-legend { color: #e5e7eb; }
        .dark .tenant-calendar .calendar-weekdays, .dark .tenant-calendar .calendar-grid, .dark .tenant-calendar .calendar-mobile-day { border-color: #374151; }
        .dark .tenant-calendar .calendar-grid { background: #374151; }
        .dark .tenant-calendar .calendar-day { background: #111827; }
        .dark .tenant-calendar .calendar-day.outside { background: #1f2937; }
        .dark .tenant-calendar .calendar-day-number { color: #f3f4f6; }
        .dark .tenant-calendar .calendar-day.outside .calendar-day-number { color: #9ca3af; }
        .dark .tenant-calendar .calendar-day-number.today { color: #fff; }
        .dark .tenant-calendar .calendar-event.start { background: #064e3b; color: #d1fae5; }
        .dark .tenant-calendar .calendar-event.end { background: #78350f; color: #fef3c7; }
        .dark .tenant-calendar .calendar-event:focus-visible { outline-color: #fbbf24; }
        @media (max-width: 1023px) {
            .tenant-calendar .calendar-desktop { display: none; }
            .tenant-calendar .calendar-mobile { display: block; margin-top: 1.25rem; }
            .tenant-calendar .calendar-mobile-day { padding: .75rem 0; border-bottom: 1px solid #e5e7eb; }
            .tenant-calendar .calendar-mobile-day:first-child { padding-top: 0; }
            .tenant-calendar .calendar-mobile-date { margin-bottom: .5rem; color: #111827; font-size: .875rem; font-weight: 600; }
            .tenant-calendar .calendar-mobile .calendar-event { padding: .5rem .75rem; font-size: .875rem; }
        }
    </style>

    <x-filament::section>
        <div class="tenant-calendar">
        <div class="calendar-header">
            <div>
                <h2 class="calendar-title">{{ $monthLabel }}</h2>
                <p class="calendar-description">
                    Tanggal mulai dan rencana selesai penyewa yang belum checkout.
                </p>
            </div>

            <div class="calendar-controls" aria-label="Navigasi bulan kalender">
                <x-filament::button color="gray" icon="heroicon-o-chevron-left" wire:click="previousMonth" aria-label="Bulan sebelumnya" />
                <x-filament::button color="gray" wire:click="currentMonth">Bulan ini</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-chevron-right" wire:click="nextMonth" aria-label="Bulan berikutnya" />
            </div>
        </div>

        <div class="calendar-legend" aria-label="Keterangan kalender">
            <span><span class="legend-dot start" aria-hidden="true"></span> Mulai kos</span>
            <span><span class="legend-dot end" aria-hidden="true"></span> Rencana selesai</span>
        </div>

        <div class="calendar-desktop">
            <div class="calendar-weekdays">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                    <span>{{ $weekday }}</span>
                @endforeach
            </div>

            <div class="calendar-grid">
                @foreach ($days as $day)
                    <div class="calendar-day {{ $day['inMonth'] ? '' : 'outside' }}">
                        <span class="calendar-day-number {{ $day['date']->toDateString() === $today ? 'today' : '' }}">{{ $day['date']->day }}</span>

                        <div class="calendar-events">
                            @foreach ($day['events'] as $event)
                                <a href="{{ $event['url'] }}"
                                    class="calendar-event {{ $event['type'] }}"
                                    aria-label="{{ $event['label'] }}: {{ $event['name'] }}{{ $event['room'] ? ', kamar ' . $event['room'] : '' }}, {{ $day['date']->format('d/m/Y') }}">
                                    <span class="calendar-event-label">{{ $event['label'] }}</span>
                                    <span class="calendar-event-name">{{ $event['name'] }}{{ $event['room'] ? ' · Kamar ' . $event['room'] : '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="calendar-mobile">
            @forelse ($eventsByDate as $date => $events)
                <div class="calendar-mobile-day">
                    <h3 class="calendar-mobile-date">
                        {{ \Carbon\CarbonImmutable::parse($date)->locale('id')->translatedFormat('d F Y') }}
                    </h3>
                    <div class="calendar-events">
                        @foreach ($events as $event)
                            <a href="{{ $event['url'] }}"
                                class="calendar-event {{ $event['type'] }}">
                                <span class="font-semibold">{{ $event['label'] }}</span> · {{ $event['name'] }}{{ $event['room'] ? ' · Kamar ' . $event['room'] : '' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="calendar-empty">Tidak ada tanggal mulai atau rencana selesai pada bulan ini. Pilih bulan lain untuk melihat jadwal.</p>
            @endforelse
        </div>

        @if (empty($eventsByDate))
            <p class="calendar-empty calendar-desktop">Tidak ada tanggal mulai atau rencana selesai pada bulan ini. Pilih bulan lain untuk melihat jadwal.</p>
        @endif

        <p class="calendar-note">
            Tanggal akhir hanya muncul jika kolom Rencana Sampai di data penyewa sudah diisi.
        </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
