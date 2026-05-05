<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RegistrationSession extends Model
{
    protected $table    = 'registration_sessions';
    protected $fillable = [
        'user_id', 'status', 'fingerprint_id',
        'expires_at', 'claimed_at',
    ];
    protected $casts = [
        'expires_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    // Ambil sesi pending yang belum kedaluwarsa (untuk ESP32)
    public static function nextPending(): ?self
    {
        // Jika ada sesi 'scanning' yang sudah >30 detik → anggap gagal, kembalikan ke pending
        self::where('status', 'scanning')
            ->where('claimed_at', '<', Carbon::now()->subSeconds(30))
            ->update(['status' => 'pending', 'claimed_at' => null]);

        return self::where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('id')
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
