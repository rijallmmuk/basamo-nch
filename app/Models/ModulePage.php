<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ModulePage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'module_id', 'title', 'type', 'content',
        'video_url', 'file_path', 'sort_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'type', 'module_id', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('materi');
    }

    protected static function booted(): void
    {
        // Auto-urut: halaman baru ditaruh di urutan terakhir modulnya.
        static::creating(function (ModulePage $page) {
            if (empty($page->sort_order)) {
                $page->sort_order = (static::where('module_id', $page->module_id)->max('sort_order') ?? 0) + 1;
            }
        });

        // Bersihkan kolom yang tak relevan dengan tipe (cegah data basi saat ganti tipe).
        static::saving(function (ModulePage $page) {
            match ($page->type) {
                'text' => [$page->video_url = null, $page->file_path = null],
                'video' => [$page->content = null, $page->file_path = null],
                'pdf' => [$page->content = null, $page->video_url = null],
                default => null,
            };
        });

        // Hapus file PDF lama saat diganti/dikosongkan agar tidak yatim di disk.
        static::updating(function (ModulePage $page) {
            $original = $page->getOriginal('file_path');
            if ($original && $page->file_path !== $original) {
                Storage::disk(config('media-library.disk_name'))->delete($original);
            }
        });

        // Hapus file PDF saat halaman dihapus.
        static::deleted(function (ModulePage $page) {
            if ($page->file_path) {
                Storage::disk(config('media-library.disk_name'))->delete($page->file_path);
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
