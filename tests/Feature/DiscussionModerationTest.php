<?php

use App\Filament\Resources\Discussions\Pages\ListDiscussions;
use App\Models\Desa;
use App\Models\Discussion;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeDiscussionModule(): Module
{
    return Module::create([
        'judul' => 'Modul Diskusi '.uniqid(),
        'slug' => 'modul-diskusi-'.uniqid(),
        'status' => 'published',
        'urutan' => 1,
    ]);
}

function makeThread(Module $module, User $author, array $overrides = []): Discussion
{
    return Discussion::create(array_merge([
        'module_id' => $module->id,
        'user_id' => $author->id,
        'isi' => 'Pertanyaan uji',
    ], $overrides));
}

it('desa_admin hanya melihat diskusi desanya', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);
    $wargaA = User::factory()->warga()->create(['desa_id' => $desaA->id]);
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);
    $module = makeDiscussionModule();

    $threadA = makeThread($module, $wargaA);
    $threadB = makeThread($module, $wargaB);

    $this->actingAs($admin);

    Livewire::test(ListDiscussions::class)
        ->assertCanSeeTableRecords([$threadA])
        ->assertCanNotSeeTableRecords([$threadB]);
});

it('super_admin melihat diskusi semua desa', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $wargaA = User::factory()->warga()->create(['desa_id' => $desaA->id]);
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);
    $module = makeDiscussionModule();

    $threadA = makeThread($module, $wargaA);
    $threadB = makeThread($module, $wargaB);

    $this->actingAs($super);

    Livewire::test(ListDiscussions::class)
        ->assertCanSeeTableRecords([$threadA, $threadB]);
});

it('admin menyematkan dan melepas sematan diskusi', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    $this->actingAs($admin);

    Livewire::test(ListDiscussions::class)->callTableAction('togglePin', $thread);
    expect($thread->refresh()->is_pinned)->toBeTrue();

    Livewire::test(ListDiscussions::class)->callTableAction('togglePin', $thread);
    expect($thread->refresh()->is_pinned)->toBeFalse();
});

it('super admin membalas pertanyaan warga (balasan atas nama admin)', function () {
    $desa = Desa::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    $this->actingAs($super);

    Livewire::test(ListDiscussions::class)
        ->callTableAction('balas', $thread, data: ['isi' => 'Ini jawaban dari admin.']);

    $reply = Discussion::where('parent_id', $thread->id)->first();

    expect($reply)->not->toBeNull()
        ->and($reply->isi)->toBe('Ini jawaban dari admin.')
        ->and($reply->user_id)->toBe($super->id)
        ->and($reply->module_id)->toBe($thread->module_id);
});

it('admin menghapus lalu memulihkan diskusi', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    $this->actingAs($admin);

    Livewire::test(ListDiscussions::class)->callTableAction('delete', $thread);
    $this->assertSoftDeleted($thread);

    Livewire::test(ListDiscussions::class)->callTableAction('restore', $thread);
    $this->assertNotSoftDeleted($thread);
});

it('desa_admin tak boleh memoderasi diskusi desa lain (policy)', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);
    $threadB = makeThread(makeDiscussionModule(), $wargaB);

    expect($admin->can('delete', $threadB))->toBeFalse()
        ->and($admin->can('update', $threadB))->toBeFalse();
});

it('super_admin boleh memoderasi diskusi desa mana pun (policy)', function () {
    $desa = Desa::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    expect($super->can('delete', $thread))->toBeTrue()
        ->and($super->can('update', $thread))->toBeTrue();
});

it('diskusi yang dihapus admin tidak tampil di portal warga', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeDiscussionModule();
    $thread = makeThread($module, $warga, ['isi' => 'Konten dimoderasi unik']);

    // Admin menghapus (soft delete) lewat resource.
    $this->actingAs($admin);
    Livewire::test(ListDiscussions::class)->callTableAction('delete', $thread);

    // Warga tak lagi melihatnya di portal.
    $this->actingAs($warga)
        ->get(route('portal.modules.discuss', $module))
        ->assertOk()
        ->assertDontSee('Konten dimoderasi unik');
});
