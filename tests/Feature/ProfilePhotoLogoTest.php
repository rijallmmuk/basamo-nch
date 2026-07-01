<?php

use App\Models\Desa;
use App\Models\RefWilayah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function profileWarga(): User
{
    return User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
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

it('foto wajib disertakan saat menyimpan', function () {
    $warga = profileWarga();

    $this->actingAs($warga)
        ->from(route('portal.profile.edit'))
        ->post(route('portal.profile.update'), [])
        ->assertSessionHasErrors('avatar');
});

it('menolak foto lebih dari 50 KB', function () {
    $warga = profileWarga();

    $this->actingAs($warga)
        ->from(route('portal.profile.edit'))
        ->post(route('portal.profile.update'), [
            // Gambar valid namun > 50 KB → ditolak pengaman server.
            'avatar' => UploadedFile::fake()->image('besar.jpg')->size(80),
        ])
        ->assertSessionHasErrors('avatar');
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

it('desa menyimpan logo sendiri & menurunkan logo kabupaten dari data wilayah', function () {
    Storage::fake(config('media-library.disk_name'));

    // Referensi minimal: kab/kota Kota Padang (berkas logo nyata di public/images/wilayah/13.71.png)
    // + satu desa/kel (dibutuhkan FK desas.wilayah_kode).
    RefWilayah::create(['kode' => '13.71', 'nama' => 'Kota Padang', 'level' => 2, 'parent_kode' => '13']);
    RefWilayah::create(['kode' => '13.71.01.1001', 'nama' => 'Pasar Gadang', 'level' => 4, 'parent_kode' => '13.71.01']);

    $desa = Desa::factory()->create(['wilayah_kode' => '13.71.01.1001']);
    $desa->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');

    expect($desa->logoUrl())->not->toBeNull()
        ->and($desa->kabupatenLogoUrl())->toBe(asset('images/wilayah/13.71.png'));
});
