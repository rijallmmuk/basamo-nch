<?php

namespace App\Filament\Forms\Components;

use App\Services\ImageOptimizer;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class OptimizedSpatieMediaLibraryFileUpload extends SpatieMediaLibraryFileUpload
{
    protected ?int $automaticCropWidth = null;

    protected ?int $automaticCropHeight = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            // Client-side resize (if the browser supports it)
            ->automaticallyResizeImagesToWidth((string) ImageOptimizer::MAX_DIMENSION)
            ->automaticallyResizeImagesToHeight((string) ImageOptimizer::MAX_DIMENSION)
            ->automaticallyResizeImagesMode('contain');

        $this->saveUploadedFileUsing(static function (OptimizedSpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            if (! method_exists($record, 'addMedia')) {
                return null;
            }

            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (UnableToCheckFileExistence $exception) {
                return null;
            }

            // Server-side resize dan konversi ke WebP
            $optimizer = app(ImageOptimizer::class);
            $crop = $component->getAutomaticCropDimensions();
            $tempOptimizedPath = $crop
                ? $optimizer->cropToTemp($file->getRealPath(), $crop['width'], $crop['height'])
                : $optimizer->optimizeToTemp($file->getRealPath());

            if ($tempOptimizedPath) {
                $fileToStore = $tempOptimizedPath;
                $originalName = pathinfo($component->getUploadedFileNameForStorage($file), PATHINFO_FILENAME) . '.webp';
                $mimeType = 'image/webp';
            } else {
                // Fallback jika optimasi gagal
                $fileToStore = $file->getRealPath();
                $originalName = $component->getUploadedFileNameForStorage($file);
                $mimeType = $file->getMimeType();
            }

            try {
                $media = $record->addMedia($fileToStore)
                    ->addCustomHeaders([...['ContentType' => $mimeType], ...$component->getCustomHeaders()])
                    ->usingFileName($originalName)
                    ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                    ->withCustomProperties($component->getCustomProperties($file))
                    ->withManipulations($component->getManipulations())
                    ->withResponsiveImagesIf($component->hasResponsiveImages())
                    ->withProperties($component->getProperties())
                    ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

                return $media->getAttributeValue('uuid');
            } finally {
                // Bersihkan temp file hasil optimasi jika berhasil dibuat
                if ($tempOptimizedPath) {
                    @unlink($tempOptimizedPath);
                }
            }
        });
    }

    /**
     * Crop otomatis yang tidak memvalidasi rasio berkas mentah. Browser lebih
     * dulu menyiapkan preview dan crop, lalu server mengulang crop yang sama
     * sebagai pengaman bagi browser/perangkat yang tidak menjalankan transformasi.
     */
    public function automaticallyCropAndResizeTo(int $width, int $height): static
    {
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('Dimensi crop harus lebih besar dari nol.');
        }

        $this->automaticCropWidth = $width;
        $this->automaticCropHeight = $height;

        $divisor = $this->greatestCommonDivisor($width, $height);

        // Disetel langsung agar FilePond tetap melakukan crop otomatis, tanpa
        // rule rasio ketat yang menolak foto asli sebelum pengaman server bekerja.
        $this->imageAspectRatio = ($width / $divisor).':'.($height / $divisor);

        return $this
            ->automaticallyCropImagesToAspectRatio()
            ->automaticallyResizeImagesMode('cover')
            ->automaticallyResizeImagesToWidth((string) $width)
            ->automaticallyResizeImagesToHeight((string) $height)
            ->automaticallyUpscaleImagesWhenResizing(false);
    }

    /** @return array{width: int, height: int}|null */
    public function getAutomaticCropDimensions(): ?array
    {
        if ($this->automaticCropWidth === null || $this->automaticCropHeight === null) {
            return null;
        }

        return [
            'width' => $this->automaticCropWidth,
            'height' => $this->automaticCropHeight,
        ];
    }

    private function greatestCommonDivisor(int $left, int $right): int
    {
        while ($right !== 0) {
            [$left, $right] = [$right, $left % $right];
        }

        return $left;
    }
}
