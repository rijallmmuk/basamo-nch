<?php

namespace App\Models;

use App\Enums\StatusSungai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu potret pembacaan sensor EWS, direkam penjadwal. Baris ini yang membuat
 * grafik tren mungkin: melihat tinggi air merangkak naik sebelum banjir, bukan
 * hanya angka sesaat.
 *
 * Nilai sensor boleh null (alat mati, satu pin gagal terbaca). Null direkam apa
 * adanya, TIDAK diganti 0: pada tinggi air, 0 berarti "sungai kering" dan di grafik
 * akan terbaca sebagai penurunan drastis yang tidak pernah terjadi.
 */
class EwsReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'ews_device_id', 'tinggi_air', 'curah_hujan', 'ph_air',
        'getaran', 'status_sungai', 'terhubung', 'direkam_pada',
    ];

    /** Rentang pH yang mungkin secara fisik. Di luar ini = sensor belum terkalibrasi. */
    public const PH_MIN = 0.0;

    public const PH_MAKS = 14.0;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tinggi_air' => 'float',
            'curah_hujan' => 'float',
            'ph_air' => 'float',
            'getaran' => 'float',
            'terhubung' => 'boolean',
            'direkam_pada' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(EwsDevice::class, 'ews_device_id');
    }

    public function status(): StatusSungai
    {
        return StatusSungai::dariTeks($this->status_sungai);
    }

    /**
     * pH di luar 0..14 tidak mungkin secara fisik. Ketiga alat yang terpasang
     * melaporkan 15,8 sehingga jelas belum terkalibrasi; angkanya tetap ditampilkan
     * apa adanya, tetapi diberi penanda supaya tidak dibaca sebagai fakta lingkungan.
     */
    public function phMencurigakan(): bool
    {
        return $this->ph_air !== null
            && ($this->ph_air < self::PH_MIN || $this->ph_air > self::PH_MAKS);
    }

    /**
     * @param  Builder<EwsReading>  $query
     */
    public function scopeSejak(Builder $query, \DateTimeInterface $waktu): void
    {
        $query->where('direkam_pada', '>=', $waktu);
    }
}
