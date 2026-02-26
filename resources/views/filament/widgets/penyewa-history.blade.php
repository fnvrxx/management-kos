<div class="space-y-4">
    {{-- Penyewa Info --}}
    @if($penyewa)
        <div class="grid grid-cols-2 gap-3 text-sm p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Nama:</span>
                <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $penyewa->nama_lengkap }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">WhatsApp:</span>
                <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $penyewa->no_wa }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Kamar:</span>
                <span class="font-semibold text-gray-900 dark:text-white ml-1">
                    {{ $penyewa->tempatKos?->nomor_kamar ?? '-' }} — {{ $penyewa->tempatKos?->lokasi ?? '' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Mulai Kos:</span>
                <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $penyewa->start_date?->format('d/m/Y') ?? '-' }}</span>
            </div>
        </div>
    @endif

    {{-- Transaction History --}}
    <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300">Riwayat Transaksi</h4>

    @forelse($transactions as $t)
        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="min-w-0 flex-1">
                <div class="font-medium text-sm text-gray-900 dark:text-white">
                    {{ $t->tanggal_pembayaran->format('d M Y') }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ $t->history_pembayaran ?? $t->metode_pembayaran }}
                </div>
            </div>
            <div class="text-right ml-4 shrink-0">
                <div class="font-bold text-green-600 dark:text-green-400">
                    Rp {{ number_format($t->nominal, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400">{{ $t->metode_pembayaran }}</div>
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 dark:text-gray-400 py-6 text-sm">
            Belum ada transaksi pembayaran.
        </div>
    @endforelse

    {{-- Total --}}
    @if($transactions->count() > 0)
        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg flex justify-between items-center">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Dibayar:</span>
            <span class="font-bold text-lg text-green-700 dark:text-green-400">
                Rp {{ number_format($transactions->sum('nominal'), 0, ',', '.') }}
            </span>
        </div>
    @endif
</div>
