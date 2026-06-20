<?php

use App\Models\Module;
use App\Models\Nagari;
use App\Models\Quiz;
use App\Models\User;
use App\Models\XpLog;
use App\Services\LmsPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeXpModule(): Module
{
    return Module::create([
        'title' => 'Modul XP '.uniqid(),
        'slug' => 'modul-xp-'.uniqid(),
        'status' => 'published',
        'sort_order' => 1,
    ]);
}

// ── Idempotensi & sumber XP ──────────────────────────────────────────
it('XP modul hanya diberi sekali walau dipanggil berkali-kali', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $module = makeXpModule();
    $svc = app(LmsPointService::class);

    $svc->awardModuleCompletion($warga, $module);
    $svc->awardModuleCompletion($warga, $module);
    $svc->awardModuleCompletion($warga, $module);

    expect(XpLog::where('user_id', $warga->id)->count())->toBe(1)
        ->and($warga->refresh()->total_xp)->toBe(50);
});

it('sumber XP berbeda tidak bertabrakan walau source_id sama', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $module = makeXpModule();
    $quiz = Quiz::create(['module_id' => $module->id, 'passing_score' => 50, 'max_attempts' => 3]);
    $svc = app(LmsPointService::class);

    $svc->awardModuleCompletion($warga, $module);        // module:module_id
    $svc->awardQuizPass($warga, $quiz);                  // quiz:quiz_id
    $svc->awardDiscussionParticipation($warga, $module); // discussion:module_id

    expect(XpLog::where('user_id', $warga->id)->count())->toBe(3)
        ->and($warga->refresh()->total_xp)->toBe(50 + 100 + 20);
});

// ── L1: leaderboard hanya warga aktif, ter-scope nagari ──────────────
it('leaderboard hanya menampilkan warga aktif se-nagari', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();
    $me = User::factory()->warga()->create(['nagari_id' => $nagariA->id, 'name' => 'Saya Sendiri', 'total_xp' => 30]);
    User::factory()->warga()->create(['nagari_id' => $nagariA->id, 'name' => 'Teman Aktif', 'total_xp' => 50]);
    User::factory()->warga()->create(['nagari_id' => $nagariA->id, 'name' => 'Teman Nonaktif', 'total_xp' => 999, 'status' => 'inactive']);
    User::factory()->warga()->create(['nagari_id' => $nagariB->id, 'name' => 'Warga Nagari Lain', 'total_xp' => 100]);

    $this->actingAs($me)
        ->get(route('portal.leaderboard'))
        ->assertOk()
        ->assertSee('Teman Aktif')
        ->assertDontSee('Teman Nonaktif')
        ->assertDontSee('Warga Nagari Lain');
});

// ── L1: warga nonaktif tidak memengaruhi peringkat/jumlah ────────────
it('peringkat & total mengabaikan warga nonaktif', function () {
    $nagari = Nagari::factory()->create();
    $me = User::factory()->warga()->create(['nagari_id' => $nagari->id, 'total_xp' => 100]);
    User::factory()->warga()->create(['nagari_id' => $nagari->id, 'total_xp' => 999, 'status' => 'inactive']);

    $resp = $this->actingAs($me)->get(route('portal.leaderboard'))->assertOk();

    expect($resp->viewData('myRank'))->toBe(1)
        ->and($resp->viewData('totalWarga'))->toBe(1);
});

// ── L2: peringkat kompetisi konsisten (seri = peringkat sama) ────────
it('peringkat daftar konsisten dengan badge saat ada seri', function () {
    $nagari = Nagari::factory()->create();
    $a = User::factory()->warga()->create(['nagari_id' => $nagari->id, 'name' => 'Andi', 'total_xp' => 100]);
    $b = User::factory()->warga()->create(['nagari_id' => $nagari->id, 'name' => 'Budi', 'total_xp' => 100]);
    $c = User::factory()->warga()->create(['nagari_id' => $nagari->id, 'name' => 'Cici', 'total_xp' => 50]);

    $resp = $this->actingAs($a)->get(route('portal.leaderboard'))->assertOk();
    $ranks = $resp->viewData('ranks');

    // Andi & Budi seri di 100 → keduanya peringkat 1; Cici peringkat 3.
    expect($ranks[$a->id])->toBe(1)
        ->and($ranks[$b->id])->toBe(1)
        ->and($ranks[$c->id])->toBe(3)
        // Badge "Posisimu" Andi juga 1 — konsisten dengan daftar.
        ->and($resp->viewData('myRank'))->toBe(1);
});
