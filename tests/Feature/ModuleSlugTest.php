<?php

use App\Models\Desa;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeModuleJudul(string $judul, ?int $desaId): Module
{
    return Module::create(['judul' => $judul, 'desa_id' => $desaId, 'status' => 'draft']);
}

it('#2 dua desa berbeda boleh punya modul berjudul sama (slug bersih per desa)', function () {
    $a = Desa::factory()->create();
    $b = Desa::factory()->create();

    $ma = makeModuleJudul('Kebersihan Lingkungan', $a->id);
    $mb = makeModuleJudul('Kebersihan Lingkungan', $b->id);

    expect($ma->slug)->toBe('kebersihan-lingkungan')
        ->and($mb->slug)->toBe('kebersihan-lingkungan')
        ->and($ma->id)->not->toBe($mb->id);
});

it('#2 modul global & modul desa boleh berjudul sama', function () {
    $desa = Desa::factory()->create();

    $global = makeModuleJudul('Mengenal Internet', null);
    $lokal = makeModuleJudul('Mengenal Internet', $desa->id);

    expect($global->slug)->toBe('mengenal-internet')
        ->and($lokal->slug)->toBe('mengenal-internet');
});

it('#2 judul sama dalam SATU desa → slug auto-suffix (tak error)', function () {
    $desa = Desa::factory()->create();

    $first = makeModuleJudul('Pertanian', $desa->id);
    $second = makeModuleJudul('Pertanian', $desa->id);

    expect($first->slug)->toBe('pertanian')
        ->and($second->slug)->toBe('pertanian-1')
        ->and($second->exists)->toBeTrue();
});

it('#1 modul yang dihapus bisa dibuat ulang berjudul sama tanpa error (reuse aman)', function () {
    $desa = Desa::factory()->create();

    $first = makeModuleJudul('Modul Lama', $desa->id);
    $first->delete(); // soft delete / arsip

    // Tak boleh melempar QueryException karena slug bentrok dgn baris ter-arsip.
    $second = makeModuleJudul('Modul Lama', $desa->id);

    expect($second->exists)->toBeTrue()
        // Spatie memperhitungkan baris ter-arsip → slug disuffix, jadi aman saat
        // modul lama dipulihkan (tak akan bentrok).
        ->and($second->slug)->toBe('modul-lama-1')
        ->and(Module::withTrashed()->where('desa_id', $desa->id)->count())->toBe(2);
});

// ── Routing by-slug: konsekuensi slug per-desa (override resolveRouteBinding) ──
it('routing: warga desa mendapat modul DESA-nya saat slug bentrok dgn global', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);

    $global = makeModuleJudul('Mengenal Internet', null);
    $lokal = makeModuleJudul('Mengenal Internet', $desa->id);
    expect($global->slug)->toBe('mengenal-internet')->and($lokal->slug)->toBe('mengenal-internet');

    $this->actingAs($warga);
    expect((new Module)->resolveRouteBinding('mengenal-internet', 'slug')?->id)->toBe($lokal->id);
});

it('routing: warga desa tanpa modul slug-itu jatuh ke modul GLOBAL', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);

    $global = makeModuleJudul('Mengenal Internet', null);
    makeModuleJudul('Mengenal Internet', $desaA->id); // hanya desaA yang punya lokal

    $this->actingAs($wargaB);
    expect((new Module)->resolveRouteBinding('mengenal-internet', 'slug')?->id)->toBe($global->id);
});

it('routing: warga tak bisa menjangkau modul desa LAIN lewat slug', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);

    $lokalA = makeModuleJudul('Khusus Lokal', $desaA->id); // hanya ada di desaA

    $this->actingAs($wargaB);
    // desaB tak punya & bukan global → tak terjangkau (null), bukan bocor ke desaA.
    expect((new Module)->resolveRouteBinding('khusus-lokal', 'slug'))->toBeNull();
});

it('routing: binding by id (panel admin) tidak ter-scope desa', function () {
    $desa = Desa::factory()->create();
    $lokal = makeModuleJudul('Khusus Desa', $desa->id);

    // Tanpa auth & field=id → resolusi default, tetap ketemu apa adanya.
    expect((new Module)->resolveRouteBinding($lokal->id)?->id)->toBe($lokal->id);
});
