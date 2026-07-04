<?php

use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('"Ingat saya" membuat token recaller (60 char) + set cookie berumur panjang', function () {
    $warga = User::factory()->warga()->create([
        'desa_id' => Desa::factory()->create()->id,
        'nik' => '3201010101010001',
        'password' => Hash::make('rahasia123'),
        'remember_token' => null, // akun baru: belum punya token recaller
    ]);

    $res = $this->post(route('login'), [
        'login' => '3201010101010001',
        'password' => 'rahasia123',
        'remember' => 'on',
    ]);

    expect(auth()->id())->toBe($warga->id);

    // 1) Token recaller di DB dibuat (Laravel Str::random(60)) → cookie recaller valid.
    $token = $warga->fresh()->remember_token;
    expect($token)->not->toBeNull()
        ->and(strlen($token))->toBe(60);

    // 2) Cookie recaller "remember_web_*" diset, berumur panjang (~5 tahun).
    $recaller = collect($res->headers->getCookies())
        ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

    expect($recaller)->not->toBeNull()
        ->and($recaller->getValue())->not->toBeEmpty()
        ->and($recaller->getExpiresTime())->toBeGreaterThan(now()->addDays(300)->timestamp);
});

it('tanpa "Ingat saya" tidak mengganti token recaller', function () {
    $warga = User::factory()->warga()->create([
        'desa_id' => Desa::factory()->create()->id,
        'nik' => '3201010101010002',
        'password' => Hash::make('rahasia123'),
        'remember_token' => 'token-lama',
    ]);

    $this->post(route('login'), [
        'login' => '3201010101010002',
        'password' => 'rahasia123',
    ]);

    // Login biasa tak menyentuh token recaller → tak ada sesi persisten lintas-restart.
    expect(auth()->id())->toBe($warga->id)
        ->and($warga->fresh()->remember_token)->toBe('token-lama');
});
