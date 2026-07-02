<?php

use App\Models\Desa;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Models\XpLog;
use App\Services\LmsPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeXpModule(): Module
{
    return Module::create([
        'judul' => 'Modul XP '.uniqid(),
        'slug' => 'modul-xp-'.uniqid(),
        'status' => 'published',
        'urutan' => 1,
    ]);
}

// ── Idempotensi & sumber XP ──────────────────────────────────────────
it('XP modul hanya diberi sekali walau dipanggil berkali-kali', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeXpModule();
    $svc = app(LmsPointService::class);

    $svc->awardModuleCompletion($warga, $module);
    $svc->awardModuleCompletion($warga, $module);
    $svc->awardModuleCompletion($warga, $module);

    expect(XpLog::where('user_id', $warga->id)->count())->toBe(1)
        ->and($warga->refresh()->total_xp)->toBe(50);
});

it('sumber XP berbeda tidak bertabrakan walau sumber_id sama', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeXpModule();
    $quiz = Quiz::create(['module_id' => $module->id, 'nilai_lulus' => 50, 'maks_percobaan' => 3]);
    $svc = app(LmsPointService::class);

    $svc->awardModuleCompletion($warga, $module);        // module:module_id
    $svc->awardQuizPass($warga, $quiz, 80);              // quiz:quiz_id (nilai biasa, tanpa bonus)
    $svc->awardDiscussionParticipation($warga, $module); // discussion:module_id

    expect(XpLog::where('user_id', $warga->id)->count())->toBe(3)
        ->and($warga->refresh()->total_xp)->toBe(50 + 100 + 20);
});

// ── L1: leaderboard hanya warga aktif, ter-scope desa ──────────────
it('leaderboard hanya menampilkan warga aktif se-desa', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $me = User::factory()->warga()->create(['desa_id' => $desaA->id, 'name' => 'Saya Sendiri', 'total_xp' => 30]);
    User::factory()->warga()->create(['desa_id' => $desaA->id, 'name' => 'Teman Aktif', 'total_xp' => 50]);
    User::factory()->warga()->create(['desa_id' => $desaA->id, 'name' => 'Teman Nonaktif', 'total_xp' => 999, 'status' => 'inactive']);
    User::factory()->warga()->create(['desa_id' => $desaB->id, 'name' => 'Warga Desa Lain', 'total_xp' => 100]);

    $this->actingAs($me)
        ->get(route('portal.leaderboard'))
        ->assertOk()
        ->assertSee('Teman Aktif')
        ->assertDontSee('Teman Nonaktif')
        ->assertDontSee('Warga Desa Lain');
});

// ── L1: warga nonaktif tidak memengaruhi peringkat/jumlah ────────────
it('peringkat & total mengabaikan warga nonaktif', function () {
    $desa = Desa::factory()->create();
    $me = User::factory()->warga()->create(['desa_id' => $desa->id, 'total_xp' => 100]);
    User::factory()->warga()->create(['desa_id' => $desa->id, 'total_xp' => 999, 'status' => 'inactive']);

    $resp = $this->actingAs($me)->get(route('portal.leaderboard'))->assertOk();

    expect($resp->viewData('myRank'))->toBe(1)
        ->and($resp->viewData('totalWarga'))->toBe(1);
});

// ── L2: peringkat kompetisi konsisten (seri = peringkat sama) ────────
it('peringkat daftar konsisten dengan badge saat ada seri', function () {
    $desa = Desa::factory()->create();
    $a = User::factory()->warga()->create(['desa_id' => $desa->id, 'name' => 'Andi', 'total_xp' => 100]);
    $b = User::factory()->warga()->create(['desa_id' => $desa->id, 'name' => 'Budi', 'total_xp' => 100]);
    $c = User::factory()->warga()->create(['desa_id' => $desa->id, 'name' => 'Cici', 'total_xp' => 50]);

    $resp = $this->actingAs($a)->get(route('portal.leaderboard'))->assertOk();
    $ranks = $resp->viewData('ranks');

    // Andi & Budi seri di 100 → keduanya peringkat 1; Cici peringkat 3.
    expect($ranks[$a->id])->toBe(1)
        ->and($ranks[$b->id])->toBe(1)
        ->and($ranks[$c->id])->toBe(3)
        // Badge "Posisimu" Andi juga 1 — konsisten dengan daftar.
        ->and($resp->viewData('myRank'))->toBe(1);
});

it('lulus kuis dengan nilai sempurna mendapat bonus keunggulan (+25), idempotent', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $quiz = Quiz::create(['module_id' => makeXpModule()->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);
    $svc = app(LmsPointService::class);

    $xp = $svc->awardQuizPass($warga, $quiz, 100);

    expect($xp)->toBe(LmsPointService::QUIZ_XP + LmsPointService::QUIZ_PERFECT_BONUS_XP)
        ->and($warga->refresh()->total_xp)->toBe(125);

    // Pemanggilan ulang (race/dobel) tak menambah apa pun.
    $svc->awardQuizPass($warga, $quiz, 100);
    expect($warga->refresh()->total_xp)->toBe(125);
});

it('lulus kuis dengan nilai biasa tetap +100 tanpa bonus', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $quiz = Quiz::create(['module_id' => makeXpModule()->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);

    $xp = app(LmsPointService::class)->awardQuizPass($warga, $quiz, 85);

    expect($xp)->toBe(LmsPointService::QUIZ_XP)
        ->and($warga->refresh()->total_xp)->toBe(100);
});
