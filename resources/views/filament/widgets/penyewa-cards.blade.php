<x-filament-widgets::widget>
    <x-filament::section heading="📋 Data Penyewa Aktif" description="Klik kartu untuk melihat riwayat pembayaran">
        {{-- Sort Buttons --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Urutkan:</span>
            <button wire:click="setSortBy('tunggakan')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all
                {{ $this->sortBy === 'tunggakan'
                    ? 'bg-red-500 text-white shadow-md'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                🔴 Tunggakan Terlama
            </button>
            <button wire:click="setSortBy('nama')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all
                {{ $this->sortBy === 'nama'
                    ? 'bg-blue-500 text-white shadow-md'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                🔤 Nama
            </button>
            <button wire:click="setSortBy('kamar')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all
                {{ $this->sortBy === 'kamar'
                    ? 'bg-blue-500 text-white shadow-md'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                🏠 Kamar
            </button>
        </div>

        @php
            $grouped = $this->getPenyewasGrouped();
            $lokasiConfig = [
                'Malang'    => ['icon' => '🏔️', 'border' => 'border-blue-200 dark:border-blue-800', 'bg' => 'bg-blue-50/50 dark:bg-blue-950/20', 'heading' => 'text-blue-700 dark:text-blue-300'],
                'Surabaya'  => ['icon' => '🏙️', 'border' => 'border-emerald-200 dark:border-emerald-800', 'bg' => 'bg-emerald-50/50 dark:bg-emerald-950/20', 'heading' => 'text-emerald-700 dark:text-emerald-300'],
                'Kediri'    => ['icon' => '🌿', 'border' => 'border-purple-200 dark:border-purple-800', 'bg' => 'bg-purple-50/50 dark:bg-purple-950/20', 'heading' => 'text-purple-700 dark:text-purple-300'],
            ];
            $defaultConfig = ['icon' => '📍', 'border' => 'border-gray-200 dark:border-gray-700', 'bg' => 'bg-gray-50/50 dark:bg-gray-900/20', 'heading' => 'text-gray-700 dark:text-gray-300'];
        @endphp

        @forelse($grouped as $lokasi => $items)
            @php $cfg = $lokasiConfig[$lokasi] ?? $defaultConfig; @endphp
            <div class="mb-6 p-4 rounded-xl border-2 {{ $cfg['border'] }} {{ $cfg['bg'] }}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold {{ $cfg['heading'] }}">
                        {{ $cfg['icon'] }} {{ $lokasi }}
                        <span class="text-sm font-normal ml-2 opacity-70">({{ $items->count() }} penyewa)</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($items as $p)
                        @php
                            $bgColor = match($p->statusColor) {
                                'success' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                                'danger'  => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                                'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                                'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                                default   => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            };

                            $borderColor = match($p->statusColor) {
                                'success' => 'border-green-300 dark:border-green-700',
                                'danger'  => 'border-red-300 dark:border-red-700',
                                'warning' => 'border-yellow-300 dark:border-yellow-700',
                                'info'    => 'border-blue-300 dark:border-blue-700',
                                default   => 'border-gray-200 dark:border-gray-700',
                            };
                        @endphp

                        <div
                            wire:click="mountAction('viewHistory', { penyewa: {{ $p->id }} })"
                            class="bg-white dark:bg-gray-900 rounded-xl border-2 {{ $borderColor }} p-4 cursor-pointer
                                   hover:shadow-lg hover:scale-[1.02] transition-all duration-200 select-none"
                        >
                            {{-- Header: nama + status --}}
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="font-bold text-base text-gray-900 dark:text-white truncate pr-2">
                                    {{ $p->nama }}
                                </h3>
                                <span class="px-2 py-1 rounded-full text-xs font-bold whitespace-nowrap {{ $bgColor }}">
                                    {{ $p->statusLabel }}
                                </span>
                            </div>

                            {{-- Details --}}
                            <div class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 text-center">📱</span>
                                    <span>{{ $p->no_wa }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-5 text-center">🏠</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $p->kamar }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-5 text-center">💰</span>
                                    <span>{{ $p->harga }} / bulan</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-5 text-center">📅</span>
                                    <span>Jatuh Tempo: <strong class="text-gray-800 dark:text-gray-200">{{ $p->jatuh_tempo }}</strong></span>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-400">
                                <span>Kos sejak {{ $p->start_date ?? '-' }}</span>
                                <span class="text-primary-500 font-medium">Lihat Riwayat →</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                Belum ada penyewa aktif.
            </div>
        @endforelse
    </x-filament::section>

    {{-- Required for Filament action modals to render --}}
    <x-filament-actions::modals />
</x-filament-widgets::widget>
