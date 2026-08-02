<?php

namespace App\Filament\Forms\Components;

use App\Support\BlockFilePath;
use App\Services\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Preset gambar teroptimasi untuk upload berbasis path (bukan Media Library).
 * Seluruh gambar dikonversi ke WebP, dibatasi dimensinya, dan dapat disimpan
 * private/public tanpa menduplikasi konfigurasi pada setiap resource.
 */
class OptimizedImageUpload extends FileUpload
{
    public function optimizeTo(
        string $disk,
        string $directory,
        string $visibility = 'private',
        int $maxDimension = ImageOptimizer::MAX_DIMENSION,
    ): static {
        return $this
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->disk($disk)
            ->directory($directory)
            ->visibility($visibility)
            // Penjaga path dipasok sendiri: pencocokan bawaan Filament membaca
            // atribut model bernama sama dengan field, dan itu gagal total untuk
            // unggahan yang bersarang di dalam blok materi. Lihat BlockFilePath.
            ->preventFilePathTampering(
                allowFilePathUsing: BlockFilePath::allow($disk, $directory),
            )
            ->automaticallyResizeImagesToWidth((string) $maxDimension)
            ->automaticallyResizeImagesToHeight((string) $maxDimension)
            ->automaticallyResizeImagesMode('contain')
            ->saveUploadedFileUsing(
                fn (TemporaryUploadedFile $file): string => app(ImageOptimizer::class)
                    ->storeOptimized(
                        $file,
                        $disk,
                        $directory,
                        $maxDimension,
                        $visibility,
                    )
            );
    }
}
