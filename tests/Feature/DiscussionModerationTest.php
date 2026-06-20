<?php

use App\Filament\Resources\Discussions\Pages\ListDiscussions;
use App\Models\Discussion;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeDiscussionModule(): Module
{
    return Module::create([
        'title' => 'Modul Diskusi '.uniqid(),
        'slug' => 'modul-diskusi-'.uniqid(),
        'status' => 'published',
        'sort_order' => 1,
    ]);
}

function makeThread(Module $module, User $author, array $overrides = []): Discussion
{
    return Discussion::create(array_merge([
        'module_id' => $module->id,
        'user_id' => $author->id,
        'body' => 'Pertanyaan uji',
    ], $overrides));
}

it('nagari_admin hanya melihat diskusi nagarinya', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagariA->id]);
    $wargaA = User::factory()->warga()->create(['nagari_id' => $nagariA->id]);
    $wargaB = User::factory()->warga()->create(['nagari_id' => $nagariB->id]);
    $module = makeDiscussionModule();

    $threadA = makeThread($module, $wargaA);
    $threadB = makeThread($module, $wargaB);

    $this->actingAs($admin);

    Livewire::test(ListDiscussions::class)
        ->assertCanSeeTableRecords([$threadA])
        ->assertCanNotSeeTableRecords([$threadB]);
});

it('super_admin melihat diskusi semua nagari', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $wargaA = User::factory()->warga()->create(['nagari_id' => $nagariA->id]);
    $wargaB = User::factory()->warga()->create(['nagari_id' => $nagariB->id]);
    $module = makeDiscussionModule();

    $threadA = makeThread($module, $wargaA);
    $threadB = makeThread($module, $wargaB);

    $this->actingAs($super);

    Livewire::test(ListDiscussions::class)
        ->assertCanSeeTableRecords([$threadA, $threadB]);
});

it('admin menyematkan dan melepas sematan diskusi', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    $this->actingAs($admin);

    Livewire::test(ListDiscussions::class)->callTableAction('togglePin', $thread);
    expect($thread->refresh()->is_pinned)->toBeTrue();

    Livewire::test(ListDiscussions::class)->callTableAction('togglePin', $thread);
    expect($thread->refresh()->is_pinned)->toBeFalse();
});

it('admin menghapus lalu memulihkan diskusi', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    $this->actingAs($admin);

    Livewire::test(ListDiscussions::class)->callTableAction('delete', $thread);
    $this->assertSoftDeleted($thread);

    Livewire::test(ListDiscussions::class)->callTableAction('restore', $thread);
    $this->assertNotSoftDeleted($thread);
});

it('nagari_admin tak boleh memoderasi diskusi nagari lain (policy)', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagariA->id]);
    $wargaB = User::factory()->warga()->create(['nagari_id' => $nagariB->id]);
    $threadB = makeThread(makeDiscussionModule(), $wargaB);

    expect($admin->can('delete', $threadB))->toBeFalse()
        ->and($admin->can('update', $threadB))->toBeFalse();
});

it('super_admin boleh memoderasi diskusi nagari mana pun (policy)', function () {
    $nagari = Nagari::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    expect($super->can('delete', $thread))->toBeTrue()
        ->and($super->can('update', $thread))->toBeTrue();
});

it('diskusi yang dihapus admin tidak tampil di portal warga', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $module = makeDiscussionModule();
    $thread = makeThread($module, $warga, ['body' => 'Konten dimoderasi unik']);

    // Admin menghapus (soft delete) lewat resource.
    $this->actingAs($admin);
    Livewire::test(ListDiscussions::class)->callTableAction('delete', $thread);

    // Warga tak lagi melihatnya di portal.
    $this->actingAs($warga)
        ->get(route('portal.modules.discuss', $module))
        ->assertOk()
        ->assertDontSee('Konten dimoderasi unik');
});
