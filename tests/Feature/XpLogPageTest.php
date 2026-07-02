<?php

use App\Models\Desa;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Models\XpLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('menampilkan riwayat XP warga dengan judul sumber', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'total_xp' => 50]);
    $module = Module::create(['judul' => 'Modul Uji XP', 'slug' => 'modul-uji-xp', 'status' => 'published', 'urutan' => 1]);

    XpLog::create([
        'user_id' => $warga->id, 'desa_id' => $desa->id,
        'sumber' => 'module', 'sumber_id' => $module->id, 'jumlah' => 50,
    ]);

    $this->actingAs($warga)->get(route('portal.xp'))
        ->assertOk()
        ->assertSee('Modul Uji XP')
        ->assertSee('+50 XP');
});

it('tidak menampilkan XP milik warga lain', function () {
    // Modul published memicu notifikasi "modul baru" ke warga; palsukan agar judulnya
    // tak muncul di modal notifikasi header dan mengotori assertion isolasi XP.
    Notification::fake();

    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $lain = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = Module::create(['judul' => 'Rahasia Orang Lain', 'slug' => 'rahasia-orang-lain', 'status' => 'published', 'urutan' => 1]);

    XpLog::create([
        'user_id' => $lain->id, 'desa_id' => $desa->id,
        'sumber' => 'module', 'sumber_id' => $module->id, 'jumlah' => 50,
    ]);

    $this->actingAs($warga)->get(route('portal.xp'))
        ->assertOk()
        ->assertDontSee('Rahasia Orang Lain')
        ->assertSee('Belum ada poin');
});

it('tetap menampilkan judul modul yang sudah diarsip di riwayat XP', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'total_xp' => 100]);
    $module = Module::create(['judul' => 'Modul Terarsip', 'slug' => 'modul-terarsip', 'status' => 'published', 'urutan' => 1]);
    $quiz = Quiz::create(['module_id' => $module->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);

    XpLog::create([
        'user_id' => $warga->id, 'desa_id' => $desa->id,
        'sumber' => 'quiz', 'sumber_id' => $quiz->id, 'jumlah' => 100,
    ]);

    $quiz->delete();
    $module->delete();

    $this->actingAs($warga)->get(route('portal.xp'))
        ->assertOk()
        ->assertSee('Kuis: Modul Terarsip');
});
