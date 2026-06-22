<?php

use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/**
 * Pengunci hardening: policy menegakkan isolasi tenant per-record SECARA MANDIRI
 * (tak bergantung pada query-scoping resource). desa_admin hanya boleh mengelola
 * record desanya; record desa lain & global ditolak; super_admin lolos semua.
 */
beforeEach(function () {
    $this->desaA = Desa::factory()->create();
    $this->desaB = Desa::factory()->create();
    $this->adminA = User::factory()->desaAdmin()->create(['desa_id' => $this->desaA->id]);
    $this->super = User::factory()->superAdmin()->create();
});

/** @return array<int, Module> [milikA, milikB, global] */
function modulesTrio(int $desaA, int $desaB): array
{
    return [
        Module::create(['desa_id' => $desaA, 'judul' => 'A', 'slug' => 'a', 'status' => 'draft']),
        Module::create(['desa_id' => $desaB, 'judul' => 'B', 'slug' => 'b', 'status' => 'draft']),
        Module::create(['desa_id' => null, 'judul' => 'G', 'slug' => 'g', 'status' => 'draft']),
    ];
}

it('ModulePolicy: desa_admin hanya modul desanya; desa lain & global ditolak', function () {
    [$a, $b, $global] = modulesTrio($this->desaA->id, $this->desaB->id);

    foreach (['view', 'update', 'delete', 'forceDelete'] as $ability) {
        expect(Gate::forUser($this->adminA)->allows($ability, $a))->toBeTrue("admin boleh $ability modul desanya");
        expect(Gate::forUser($this->adminA)->denies($ability, $b))->toBeTrue("admin ditolak $ability modul desa lain");
        expect(Gate::forUser($this->adminA)->denies($ability, $global))->toBeTrue("admin ditolak $ability modul global");
        expect(Gate::forUser($this->super)->allows($ability, $b))->toBeTrue("super_admin lolos $ability");
    }
});

it('QuizPolicy: ikut desa modul induk', function () {
    [$a, $b] = modulesTrio($this->desaA->id, $this->desaB->id);
    $quizA = Quiz::create(['module_id' => $a->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);
    $quizB = Quiz::create(['module_id' => $b->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);

    expect(Gate::forUser($this->adminA)->allows('update', $quizA))->toBeTrue();
    expect(Gate::forUser($this->adminA)->denies('update', $quizB))->toBeTrue();
    expect(Gate::forUser($this->super)->allows('update', $quizB))->toBeTrue();
});

it('DesaUnitPolicy: hanya sub-unit desanya', function () {
    $unitA = DesaUnit::create(['desa_id' => $this->desaA->id, 'nama' => 'Jorong A']);
    $unitB = DesaUnit::create(['desa_id' => $this->desaB->id, 'nama' => 'Jorong B']);

    expect(Gate::forUser($this->adminA)->allows('update', $unitA))->toBeTrue();
    expect(Gate::forUser($this->adminA)->denies('update', $unitB))->toBeTrue();
    expect(Gate::forUser($this->super)->allows('update', $unitB))->toBeTrue();
});

it('UmkmProfilePolicy: hanya profil usaha di desanya', function () {
    $ownerA = User::factory()->umkmOwner()->create(['desa_id' => $this->desaA->id]);
    $ownerB = User::factory()->umkmOwner()->create(['desa_id' => $this->desaB->id]);

    $profileA = UmkmProfile::create(['desa_id' => $this->desaA->id, 'user_id' => $ownerA->id, 'nama_usaha' => 'Usaha A', 'whatsapp' => '08111', 'status' => 'active']);
    $profileB = UmkmProfile::create(['desa_id' => $this->desaB->id, 'user_id' => $ownerB->id, 'nama_usaha' => 'Usaha B', 'whatsapp' => '08222', 'status' => 'active']);

    expect(Gate::forUser($this->adminA)->allows('update', $profileA))->toBeTrue();
    expect(Gate::forUser($this->adminA)->denies('update', $profileB))->toBeTrue();
    expect(Gate::forUser($this->super)->allows('update', $profileB))->toBeTrue();
});

it('desa_admin tanpa desa_id tak bisa kelola record manapun (guard null)', function () {
    $orphan = User::factory()->desaAdmin()->create(['desa_id' => null]);
    [$a] = modulesTrio($this->desaA->id, $this->desaB->id);

    expect(Gate::forUser($orphan)->denies('update', $a))->toBeTrue();
});
