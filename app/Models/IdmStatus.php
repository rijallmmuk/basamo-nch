<?php

namespace App\Models;

use App\Enums\StatusIdm;
use App\Models\Concerns\BelongsToNagari;
use App\Services\Idm\IdmKemendesaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status IDM satu nagari untuk satu tahun (potret resmi Kemendesa): skor indeks 0-1 +
 * kelas kemajuan (Tertinggal/Berkembang/Maju/Mandiri). Ditarik via {@see IdmKemendesaService}.
 * `status` disimpan sebagai string mentah API; petakan ke enum lewat {@see statusEnum}
 * agar nilai tak terduga tak pernah membuat cast gagal.
 */
class IdmStatus extends Model
{
    use BelongsToNagari;

    protected $fillable = [
        'nagari_id', 'tahun', 'skor', 'status', 'target_status', 'skor_minimal', 'penambahan',
        'skor_iks', 'skor_ike', 'skor_ikl', 'fetched_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'skor' => 'decimal:4',
            'skor_minimal' => 'decimal:4',
            'penambahan' => 'decimal:6',
            'skor_iks' => 'decimal:4',
            'skor_ike' => 'decimal:4',
            'skor_ikl' => 'decimal:4',
            'fetched_at' => 'datetime',
        ];
    }

    /** Enum status (null bila API mengembalikan nilai di luar 5 kelas resmi). */
    public function statusEnum(): ?StatusIdm
    {
        return StatusIdm::tryFrom($this->status);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(IdmIndicator::class)->orderBy('id');
    }
}
