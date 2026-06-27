<?php

namespace App\Models;

use App\Enums\ModuleBlockType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ModulePage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'module_id', 'judul', 'blocks', 'urutan',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'urutan' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['judul', 'module_id', 'urutan'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('materi');
    }

    protected static function booted(): void
    {
        // Auto-urut: halaman baru ditaruh di urutan terakhir modulnya.
        static::creating(function (ModulePage $page) {
            if (empty($page->urutan)) {
                $page->urutan = (static::where('module_id', $page->module_id)->max('urutan') ?? 0) + 1;
            }
        });

        // Hapus berkas yang sudah tak dirujuk blok mana pun saat halaman disimpan ulang
        // (mis. blok PDF/gambar/audio/lampiran dibuang atau berkasnya diganti).
        static::updating(function (ModulePage $page) {
            $removed = array_diff(
                static::filePathsFromBlocks($page->getOriginal('blocks')),
                static::filePathsFromBlocks($page->blocks),
            );

            static::deleteFiles($removed);
        });

        // Hapus semua berkas blok saat halaman dihapus.
        static::deleted(function (ModulePage $page) {
            static::deleteFiles(static::filePathsFromBlocks($page->blocks));
        });
    }

    /**
     * Kumpulkan path berkas terunggah dari array blok (PDF/gambar/audio/lampiran).
     *
     * @param  array<int, array<string, mixed>>|string|null  $blocks
     * @return array<int, string>
     */
    public static function filePathsFromBlocks(array|string|null $blocks): array
    {
        if (is_string($blocks)) {
            $blocks = json_decode($blocks, true) ?: [];
        }

        return collect($blocks ?? [])
            ->filter(fn ($block) => ModuleBlockType::tryFrom($block['type'] ?? '')?->storesFile())
            ->pluck('data.file')
            ->filter()
            ->values()
            ->all();
    }

    /** @param  array<int, string>  $paths */
    private static function deleteFiles(array $paths): void
    {
        $disk = Storage::disk(config('media-library.disk_name'));

        foreach ($paths as $path) {
            $disk->delete($path);
        }
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
