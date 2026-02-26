<?php

namespace App\Filament\Widgets;

use App\Models\Penyewa;
use App\Models\TransaksiKos;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class PenyewaCardWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.penyewa-cards';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2; // After StatsOverview

    public string $sortBy = 'tunggakan';

    public function setSortBy(string $sort): void
    {
        $this->sortBy = $sort;
    }

    /**
     * Get all active penyewa with computed status for the cards.
     * Sorted by selected criteria (default: longest tunggakan first).
     */
    public function getPenyewas()
    {
        $statusOrder = [
            'tunggakan'    => 0,
            'cicilan'      => 1,
            'belum_bayar'  => 2,
            'lunas'        => 3,
            'belum_assign' => 4,
        ];

        $collection = Penyewa::with(['tempatKos'])
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>', now()))
            ->get()
            ->map(function ($p) {
                $kamar = $p->tempatKos;

                $status      = 'belum_assign';
                $statusLabel = '⬜ BELUM ASSIGN';
                $statusColor = 'gray';

                if ($kamar) {
                    $partial = TransaksiKos::getPartialPayment($kamar);

                    if (!$kamar->tgl_jatuh_tempo) {
                        if ($partial > 0) {
                            $pFmt = number_format($partial, 0, ',', '.');
                            $hFmt = number_format($kamar->harga, 0, ',', '.');
                            $status      = 'cicilan';
                            $statusLabel = "💰 Rp {$pFmt} / {$hFmt}";
                            $statusColor = 'info';
                        } else {
                            $status      = 'belum_bayar';
                            $statusLabel = '🕐 BELUM BAYAR';
                            $statusColor = 'warning';
                        }
                    } elseif ($kamar->tgl_jatuh_tempo->gt(Carbon::today())) {
                        $status      = 'lunas';
                        $statusLabel = '✅ LUNAS';
                        $statusColor = 'success';
                    } else {
                        if ($partial > 0) {
                            $pFmt = number_format($partial, 0, ',', '.');
                            $hFmt = number_format($kamar->harga, 0, ',', '.');
                            $status      = 'cicilan';
                            $statusLabel = "💰 Rp {$pFmt} / {$hFmt}";
                            $statusColor = 'info';
                        } else {
                            $status      = 'tunggakan';
                            $statusLabel = '❌ TUNGGAKAN';
                            $statusColor = 'danger';
                        }
                    }
                }

                return (object) [
                    'id'                  => $p->id,
                    'nama'                => $p->nama_lengkap,
                    'no_wa'               => $p->no_wa,
                    'lokasi'              => $kamar?->lokasi ?? 'Belum Assign',
                    'kamar'               => $kamar ? $kamar->nomor_kamar . ' — ' . $kamar->lokasi : '-',
                    'kamar_kode'          => $kamar?->nomor_kamar ?? 'ZZZ',
                    'harga'               => $kamar ? 'Rp ' . number_format($kamar->harga, 0, ',', '.') : '-',
                    'jatuh_tempo'         => $kamar?->tgl_jatuh_tempo?->format('d/m/Y') ?? '-',
                    'tgl_jatuh_tempo_raw' => $kamar?->tgl_jatuh_tempo,
                    'status'              => $status,
                    'statusLabel'         => $statusLabel,
                    'statusColor'         => $statusColor,
                    'start_date'          => $p->start_date?->format('d/m/Y'),
                ];
            });

        // Sort based on selected criteria
        $sorted = match ($this->sortBy) {
            'nama'  => $collection->sortBy('nama')->values(),
            'kamar' => $collection->sortBy('kamar_kode')->values(),
            default => $collection->sort(function ($a, $b) use ($statusOrder) {
                $aPriority = $statusOrder[$a->status] ?? 99;
                $bPriority = $statusOrder[$b->status] ?? 99;

                if ($aPriority !== $bPriority) {
                    return $aPriority - $bPriority;
                }

                // Within same status, oldest jatuh tempo first (longest tunggakan on top)
                $aTs = $a->tgl_jatuh_tempo_raw?->timestamp ?? PHP_INT_MAX;
                $bTs = $b->tgl_jatuh_tempo_raw?->timestamp ?? PHP_INT_MAX;
                return $aTs - $bTs;
            })->values(),
        };

        return $sorted;
    }

    /**
     * Get penyewas grouped by lokasi for the dashboard card layout.
     */
    public function getPenyewasGrouped()
    {
        return $this->getPenyewas()->groupBy('lokasi');
    }

    /**
     * Filament Action: show payment history modal when a card is clicked.
     */
    public function viewHistoryAction(): Action
    {
        return Action::make('viewHistory')
            ->modalHeading(function (array $arguments) {
                $p = Penyewa::find($arguments['penyewa'] ?? 0);
                return 'Riwayat Pembayaran — ' . ($p->nama_lengkap ?? '-');
            })
            ->modalContent(function (array $arguments) {
                $penyewa = Penyewa::with('tempatKos')->find($arguments['penyewa'] ?? 0);
                $transactions = TransaksiKos::where('id_penyewa', $arguments['penyewa'] ?? 0)
                    ->orderBy('tanggal_pembayaran', 'desc')
                    ->get();

                return view('filament.widgets.penyewa-history', [
                    'penyewa'      => $penyewa,
                    'transactions' => $transactions,
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth('lg');
    }
}
