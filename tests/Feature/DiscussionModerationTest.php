<?php

use App\Filament\Resources\Discussions\DiscussionResource;
use App\Filament\Resources\Discussions\Pages\ListDiscussions;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Models\Desa;
use App\Models\Discussion;
use App\Models\Module;
use App\Models\User;
use App\Notifications\DiscussionReplied;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

it('balasan admin memberi tahu warga penanya', function () {
    Notification::fake();
    $desa = Desa::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $thread = makeThread(makeDiscussionModule(), $warga);

    $this->actingAs($super);

    Livewire::test(ListDiscussions::class)
        ->callTableAction('balas', $thread, data: ['isi' => 'Jawaban dari admin.']);

    Notification::assertSentTo($warga, DiscussionReplied::class);
});

it('balasan warga lain memberi tahu penanya, tapi tidak memberi tahu diri sendiri', function () {
    Notification::fake();
    $desa = Desa::factory()->create();
    $module = makeDiscussionModule();
    $penanya = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $pembalas = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $thread = makeThread($module, $penanya);

    $this->actingAs($pembalas)
        ->post(route('portal.modules.discuss.reply', [$module, $thread]), ['isi' => 'Jawaban warga lain.'])
        ->assertRedirect();

    Notification::assertSentTo($penanya, DiscussionReplied::class);
    Notification::assertNotSentTo($pembalas, DiscussionReplied::class);
});

it('membalas thread sendiri tidak memicu notifikasi', function () {
    Notification::fake();
    $desa = Desa::factory()->create();
    $module = makeDiscussionModule();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $thread = makeThread($module, $warga);

    $this->actingAs($warga)
        ->post(route('portal.modules.discuss.reply', [$module, $thread]), ['isi' => 'Menambahkan info sendiri.']);

    Notification::assertNothingSent();
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

it('daftar modul: aksi "Kelola Diskusi" mengarah ke daftar diskusi ter-filter modul', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $module = makeDiscussionModule();

    Livewire::test(ListModules::class)
        ->assertTableActionExists('kelolaDiskusi')
        ->assertTableActionHasUrl(
            'kelolaDiskusi',
            DiscussionResource::getUrl('index', ['filters' => ['module' => ['value' => $module->id]]]),
            record: $module,
        );
});

it('daftar diskusi ter-filter modul hanya menampilkan diskusi modul tersebut', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);

    $modulA = makeDiscussionModule();
    $modulB = makeDiscussionModule();
    $diskA = makeThread($modulA, $warga);
    $diskB = makeThread($modulB, $warga);

    // Simulasi membuka URL aksi "Kelola Diskusi": filter modul dari query string
    // HARUS langsung menyaring (deferFilters(false) — tanpa ini filter URL tertimpa).
    Livewire::withQueryParams(['filters' => ['module' => ['value' => $modulA->id]]])
        ->test(ListDiscussions::class)
        ->assertCanSeeTableRecords([$diskA])
        ->assertCanNotSeeTableRecords([$diskB]);
});
