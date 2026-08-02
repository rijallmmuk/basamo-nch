<?php

namespace App\Models;

use App\Enums\ModuleBlockType;
use App\Observers\ModuleObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Materi = satu halaman isi modul sekaligus unit penyelesaian warga. Isinya array
 * blok bertipe teks/video/pdf/gambar/audio/lampiran ({@see ModuleBlockType}).
 */
class Materi extends Model
{
    use HasFactory;
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
            // Simpan riwayat referensi blok agar path lama tetap dapat diaudit
            // dan dipulihkan bila ada perubahan yang tidak disengaja.
            ->logOnly(['judul', 'module_id', 'urutan', 'blocks'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('materi');
    }

    protected static function booted(): void
    {
        // Invariant terakhir sebelum database: blok yang memang menyimpan file
        // tidak boleh pernah tersimpan tanpa referensi file yang sah. Validasi
        // form tetap ada, tetapi model juga dipakai batch authoring, impor, dan
        // kode internal. Tanpa pagar ini, disk yang sementara tidak terbaca dapat
        // membuat FileUpload terhidrasi kosong lalu penyimpanan berikutnya
        // menimpa JSON `blocks` dan memutus referensi file lama.
        static::saving(function (Materi $materi): void {
            foreach (array_values($materi->blocks ?? []) as $index => $block) {
                $typeValue = $block['type'] ?? null;
                $type = is_string($typeValue) ? ModuleBlockType::tryFrom($typeValue) : null;

                if (! $type?->storesFile()) {
                    continue;
                }

                $path = $block['data']['file'] ?? null;

                if (! is_string($path) || trim($path) === '' || ! static::isSafeBlockFilePath($type, $path)) {
                    throw ValidationException::withMessages([
                        "blocks.{$index}.data.file" => 'Referensi file materi tidak lengkap. File lama dipertahankan; muat ulang halaman dan periksa penyimpanan sebelum menyimpan.',
                    ]);
                }
            }
        });

        // Auto-urut: materi baru ditaruh di urutan terakhir modulnya.
        static::creating(function (Materi $materi) {
            if (empty($materi->urutan)) {
                $materi->urutan = (static::where('module_id', $materi->module_id)->max('urutan') ?? 0) + 1;
            }
        });

        // Modul menjadi terlihat warga begitu materi PERTAMANYA ada (tak ada lagi saklar
        // publish di modul). Di titik itulah pengumuman "modul baru" dikirim, sekali saja.
        static::created(function (Materi $materi): void {
            $module = $materi->module;

            if (! $module || $module->materis()->count() !== 1) {
                return;
            }

            DB::afterCommit(fn () => app(ModuleObserver::class)->notifyWarga($module));
        });

        // Berkas yang diganti/dilepas SENGAJA tidak langsung dihapus saat update.
        // Ruang disk lebih murah daripada kehilangan materi produksi akibat state
        // form kosong, kegagalan disk sesaat, atau deploy yang belum membawa file
        // privat. Pembersihan orphan harus menjadi operasi eksplisit setelah backup.

        // Hapus semua berkas blok saat materi dihapus.
        static::deleted(function (Materi $materi): void {
            $paths = static::filePathsFromBlocks($materi->blocks);

            DB::afterCommit(fn () => static::deleteFiles($paths));
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
            ->filter(function ($block): bool {
                $type = is_array($block) ? ($block['type'] ?? null) : null;

                return is_string($type) && (ModuleBlockType::tryFrom($type)?->storesFile() ?? false);
            })
            ->pluck('data.file')
            ->filter()
            ->values()
            ->all();
    }

    public static function isSafeBlockFilePath(ModuleBlockType $type, string $path): bool
    {
        $directory = match ($type) {
            ModuleBlockType::Pdf => 'modules/blocks/pdf/',
            ModuleBlockType::Gambar => 'modules/blocks/gambar/',
            ModuleBlockType::Audio => 'modules/blocks/audio/',
            ModuleBlockType::Lampiran => 'modules/blocks/lampiran/',
            default => '',
        };

        if ($directory === '' || ! str_starts_with($path, $directory)) {
            return false;
        }

        $name = substr($path, strlen($directory));

        return $name !== '' && ! str_contains($name, '/') && ! str_contains($name, '..');
    }

    /** @param  array<int, string>  $paths */
    private static function deleteFiles(array $paths): void
    {
        $disk = Storage::disk(config('slc.material_disk'));

        foreach ($paths as $path) {
            $disk->delete($path);
        }
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
