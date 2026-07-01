<?php

use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeWargaWithNotif(array $data = [], bool $read = false): User
{
    $user = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);

    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\DiscussionReplied',
        'data' => array_merge([
            'title' => 'Ada balasan untuk pertanyaanmu',
            'body' => 'Admin membalas di modul Belanja Online',
            'icon' => 'heroicon-s-chat-bubble-left-right',
            'url' => '/portal',
        ], $data),
        'read_at' => $read ? now() : null,
    ]);

    return $user;
}

it('endpoint markAllRead menandai semua notifikasi belum dibaca jadi terbaca', function () {
    $user = makeWargaWithNotif();
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\NewModulePublished',
        'data' => ['title' => 'Modul baru'],
        'read_at' => null,
    ]);

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->post(route('portal.notifications.read'))
        ->assertNoContent();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('modal notifikasi di header merender isi & judul notifikasi', function () {
    $user = makeWargaWithNotif(['title' => 'Modul Baru Tersedia', 'body' => 'Cek sekarang']);

    $this->actingAs($user)
        ->get(route('portal.home'))
        ->assertOk()
        ->assertSee('Notifikasi')
        ->assertSee('Modul Baru Tersedia');
});

it('menandai dibaca hanya untuk notifikasi milik sendiri', function () {
    $mine = makeWargaWithNotif();
    $other = makeWargaWithNotif();

    $this->actingAs($mine)->post(route('portal.notifications.read'))->assertNoContent();

    expect($mine->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($other->fresh()->unreadNotifications()->count())->toBe(1);
});
