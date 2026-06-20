<?php

use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Services\UmkmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

it('soft-delete produk mempertahankan foto (tak hilang saat dipulihkan)', function () {
    Storage::fake(config('media-library.disk_name'));
    $profile = UmkmProfile::factory()->create();
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id]);
    $product->addMedia(UploadedFile::fake()->image('foto.jpg'))->toMediaCollection('photos');

    $product->delete(); // soft delete

    expect(Media::where('model_id', $product->id)->count())->toBe(1);
});

it('hard-delete produk menghapus fotonya', function () {
    Storage::fake(config('media-library.disk_name'));
    $profile = UmkmProfile::factory()->create();
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id]);
    $product->addMedia(UploadedFile::fake()->image('foto.jpg'))->toMediaCollection('photos');

    $product->forceDelete();

    expect(Media::where('model_id', $product->id)->count())->toBe(0);
});

it('batas foto produk dihormati (maks 5)', function () {
    Storage::fake(config('media-library.disk_name'));
    $profile = UmkmProfile::factory()->create();
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id]);

    $photos = collect(range(1, 7))
        ->map(fn (int $i) => UploadedFile::fake()->image("foto{$i}.jpg"))
        ->all();

    app(UmkmService::class)->attachPhotos($product, $photos);

    expect($product->fresh()->getMedia('photos'))->toHaveCount(5);
});
