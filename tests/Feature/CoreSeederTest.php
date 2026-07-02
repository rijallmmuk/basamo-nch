<?php

use App\Models\User;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('di lingkungan non-produksi super admin memakai sandi demo tanpa paksa ganti', function () {
    $this->seed(CoreSeeder::class);

    $superAdmin = User::where('username', 'superadmin')->firstOrFail();

    expect(Hash::check('password', $superAdmin->password))->toBeTrue()
        ->and($superAdmin->must_change_password)->toBeFalse();
});

it('di produksi super admin dibuat dengan sandi acak dan wajib ganti saat login pertama', function () {
    app()['env'] = 'production';

    try {
        // Panggil langsung (bukan artisan db:seed) — hindari prompt konfirmasi produksi.
        $seeder = new CoreSeeder;
        $seeder->setContainer(app());
        $seeder->__invoke();
    } finally {
        app()['env'] = 'testing';
    }

    $superAdmin = User::where('username', 'superadmin')->firstOrFail();

    expect(Hash::check('password', $superAdmin->password))->toBeFalse()
        ->and($superAdmin->must_change_password)->toBeTrue();
});
