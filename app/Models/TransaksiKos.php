<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TransaksiKos extends Model
{
    protected $table = 'transaksi_kos';
    protected $guarded = [];
    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'periode_mulai'      => 'date',
        'periode_selesai'    => 'date',
    ];

    public function tempatKos()
    {
        return $this->belongsTo(TempatKos::class, 'id_tempat_kos');
    }

    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa');
    }

    /**
     * Recompute tgl_jatuh_tempo for a room based on total payments.
     *
     * Logic: tgl_jatuh_tempo = penyewa.start_date + floor(totalPaid / harga) months
     * This properly handles installments — partial payments don't advance the due date.
     */
    public static function recomputeJatuhTempo(TempatKos $kamar): void
    {
        if (!$kamar->id_penyewa || !$kamar->harga || $kamar->harga <= 0) {
            return;
        }

        $penyewa = $kamar->penyewa;
        if (!$penyewa || !$penyewa->start_date) {
            return;
        }

        // Sum ALL payments from this tenant for this room
        $totalPaid = static::where('id_tempat_kos', $kamar->id)
            ->where('id_penyewa', $kamar->id_penyewa)
            ->sum('nominal');

        // How many full months are covered?
        $monthsCovered = (int) floor($totalPaid / $kamar->harga);

        // tgl_jatuh_tempo = start_date + monthsCovered months
        // e.g., check-in Jan 6 + 1 month = Feb 6 (due date for 1st month)
        $kamar->tgl_jatuh_tempo = Carbon::parse($penyewa->start_date)
            ->addMonths($monthsCovered);

        $kamar->saveQuietly();
    }

    /**
     * Get the partial payment amount toward the current (unpaid) period.
     * Returns 0 if fully paid up, or the partial amount if cicilan.
     */
    public static function getPartialPayment(TempatKos $kamar): int
    {
        if (!$kamar->id_penyewa || !$kamar->harga || $kamar->harga <= 0) {
            return 0;
        }

        $totalPaid = static::where('id_tempat_kos', $kamar->id)
            ->where('id_penyewa', $kamar->id_penyewa)
            ->sum('nominal');

        return (int) ($totalPaid % $kamar->harga);
    }

    /**
     * Get the outstanding amount (tanggungan) for the current period.
     * = harga - partial payment toward current month.
     */
    public static function getTanggungan(TempatKos $kamar): int
    {
        if (!$kamar->harga || $kamar->harga <= 0) {
            return 0;
        }

        $partial = self::getPartialPayment($kamar);
        return $kamar->harga - $partial;
    }

    // After saving a transaction, recompute tgl_jatuh_tempo AND sync reminders
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($transaksi) {
            if ($transaksi->id_tempat_kos) {
                $kamar = TempatKos::find($transaksi->id_tempat_kos);
                if ($kamar) {
                    self::recomputeJatuhTempo($kamar);
                    self::syncReminders($kamar);
                }
            }
        });

        static::deleted(function ($transaksi) {
            if ($transaksi->id_tempat_kos) {
                $kamar = TempatKos::find($transaksi->id_tempat_kos);
                if ($kamar) {
                    self::recomputeJatuhTempo($kamar);
                    self::syncReminders($kamar);
                }
            }
        });
    }

    /**
     * Sync reminders after a payment is made.
     * - If penyewa is now LUNAS (jatuh_tempo > today), delete pending un-sent reminders.
     * - If still overdue, update tanggungan and end_date on pending reminders.
     */
    public static function syncReminders(TempatKos $kamar): void
    {
        if (!$kamar->id_penyewa) return;

        $kamar->refresh();

        $pendingReminders = Reminder::where('id_penyewa', $kamar->id_penyewa)
            ->where('broadcast', false)
            ->get();

        if ($pendingReminders->isEmpty()) return;

        // If penyewa is now LUNAS (jatuh_tempo > today), remove pending reminders
        if ($kamar->tgl_jatuh_tempo && $kamar->tgl_jatuh_tempo->gt(Carbon::today())) {
            Reminder::where('id_penyewa', $kamar->id_penyewa)
                ->where('broadcast', false)
                ->delete();
            return;
        }

        // Still overdue — update tanggungan and end_date on pending reminders
        $tanggungan = self::getTanggungan($kamar);

        foreach ($pendingReminders as $reminder) {
            $reminder->update([
                'tanggungan' => $tanggungan > 0 ? $tanggungan : $kamar->harga,
                'end_date'   => $kamar->tgl_jatuh_tempo,
            ]);
        }
    }
}
