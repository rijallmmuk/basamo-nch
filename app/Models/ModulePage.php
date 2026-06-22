<?php

namespace App\Models;

use App\Enums\ModulePageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ModulePage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'module_id', 'judul', 'tipe', 'konten',
        'url_video', 'path_file', 'urutan',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tipe' => ModulePageType::class,
            'urutan' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['judul', 'tipe', 'module_id', 'urutan'])
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

        // Bersihkan kolom yang tak relevan dengan tipe (cegah data basi saat ganti tipe).
        static::saving(function (ModulePage $page) {
            match ($page->tipe) {
                ModulePageType::Text => [$page->url_video = null, $page->path_file = null],
                ModulePageType::Video => [$page->konten = null, $page->path_file = null],
                ModulePageType::Pdf => [$page->konten = null, $page->url_video = null],
                default => null,
            };
        });

        // Hapus file PDF lama saat diganti/dikosongkan agar tidak yatim di disk.
        static::updating(function (ModulePage $page) {
            $original = $page->getOriginal('path_file');
            if ($original && $page->path_file !== $original) {
                Storage::disk(config('media-library.disk_name'))->delete($original);
            }
        });

        // Hapus file PDF saat halaman dihapus.
        static::deleted(function (ModulePage $page) {
            if ($page->path_file) {
                Storage::disk(config('media-library.disk_name'))->delete($page->path_file);
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
