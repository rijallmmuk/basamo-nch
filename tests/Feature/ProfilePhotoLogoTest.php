<?php

use App\Models\Nagari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function profileWarga(): User
{
    return User::factory()->warga()->create(['nagari_id' => Nagari::factory()->create()->id]);
}

it('halaman profil warga bisa dibuka', function () {
    $this->actingAs(profileWarga())
        ->get(route('portal.profile.edit'))
        ->assertOk()
        ->assertSee('Profil Saya');
});

it('warga mengunggah foto profil', function () {
    Storage::fake(config('media-library.disk_name'));
    $warga = profileWarga();

    $this->actingAs($warga)
        ->post(route('portal.profile.update'), [
            'avatar' => UploadedFile::fake()->image('me.jpg', 300, 300),
        ])
        ->assertRedirect(route('portal.profile.edit'));

    expect($warga->refresh()->avatarUrl())->not->toBeNull()
        ->and($warga->getMedia('avatar'))->toHaveCount(1);
});

it('mengunggah foto baru mengganti yang lama (singleFile)', function () {
    Storage::fake(config('media-library.disk_name'));
    $warga = profileWarga();
    $warga->addMedia(UploadedFile::fake()->image('lama.jpg'))->toMediaCollection('avatar');

    $this->actingAs($warga)
        ->post(route('portal.profile.update'), [
            'avatar' => UploadedFile::fake()->image('baru.jpg'),
        ]);

    expect($warga->fresh()->getMedia('avatar'))->toHaveCount(1);
});

it('warga menghapus foto profil (kembali ke inisial)', function () {
    Storage::fake(config('media-library.disk_name'));
    $warga = profileWarga();
    $warga->addMedia(UploadedFile::fake()->image('me.jpg'))->toMediaCollection('avatar');

    $this->actingAs($warga)
        ->post(route('portal.profile.update'), ['remove_avatar' => '1']);

    expect($warga->refresh()->avatarUrl())->toBeNull();
});

it('menolak file non-gambar sebagai foto profil', function () {
    $warga = profileWarga();

    $this->actingAs($warga)
        ->from(route('portal.profile.edit'))
        ->post(route('portal.profile.update'), [
            'avatar' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');
});

it('nagari menyimpan logo nagari & logo kabupaten', function () {
    Storage::fake(config('media-library.disk_name'));
    $nagari = Nagari::factory()->create();

    $nagari->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');
    $nagari->addMedia(UploadedFile::fake()->image('kabupaten.png'))->toMediaCollection('logo_kabupaten');

    expect($nagari->logoUrl())->not->toBeNull()
        ->and($nagari->kabupatenLogoUrl())->not->toBeNull();
});
