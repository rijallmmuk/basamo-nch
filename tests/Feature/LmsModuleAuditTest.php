<?php

use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\Nagari;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Services\LmsProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** Modul published + N halaman teks. */
function makeModuleWithPages(int $pageCount, ?int $nagariId = null): Module
{
    $module = Module::create([
        'title' => 'Modul '.uniqid(),
        'slug' => 'modul-'.uniqid(),
        'status' => 'published',
        'sort_order' => 1,
        'nagari_id' => $nagariId,
    ]);

    for ($i = 1; $i <= $pageCount; $i++) {
        ModulePage::create([
            'module_id' => $module->id,
            'title' => "Materi $i",
            'type' => 'text',
            'content' => "Isi materi $i.",
            'sort_order' => $i,
        ]);
    }

    return $module;
}

// ── #1 Keamanan: XSS pada konten materi ──────────────────────────────
it('konten materi disanitasi dari script saat ditampilkan ke warga', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);

    $module = Module::create([
        'title' => 'Modul XSS', 'slug' => 'modul-xss', 'status' => 'published', 'sort_order' => 1,
    ]);
    $page = ModulePage::create([
        'module_id' => $module->id, 'title' => 'Materi', 'type' => 'text',
        'content' => '<script>alert(1)</script><p>Konten aman</p>', 'sort_order' => 1,
    ]);

    $this->actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk()
        ->assertSee('Konten aman')
        ->assertDontSee('alert(1)');
});

// ── #3/#4 Penyelesaian: rekonsiliasi setelah halaman dihapus ──────────
it('menyelesaikan modul setelah halaman tersisa dibuka semua, walau ada halaman dihapus', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $module = makeModuleWithPages(4);
    $pages = $module->pages()->orderBy('sort_order')->get();

    $service = app(LmsProgressService::class);

    // Buka 3 dari 4 halaman → belum selesai.
    $service->markPageCompleted($warga, $module, $pages[0]);
    $service->markPageCompleted($warga, $module, $pages[1]);
    $service->markPageCompleted($warga, $module, $pages[2]);

    $progress = UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)->first();
    expect($progress->status)->toBe('in_progress');

    // Admin menghapus halaman ke-4 (yang belum dibuka).
    $pages[3]->delete();

    // Warga membuka ulang halaman yang sudah selesai → harus memicu rekonsiliasi.
    $service->markPageCompleted($warga, $module, $pages[0]);

    $progress->refresh();
    expect($progress->status)->toBe('completed')
        ->and($progress->completed_at)->not->toBeNull()
        ->and($warga->refresh()->total_xp)->toBe(50);
});

it('membuang ID halaman hantu dari pages_completed', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $module = makeModuleWithPages(3);
    $pages = $module->pages()->orderBy('sort_order')->get();
    $service = app(LmsProgressService::class);

    $service->markPageCompleted($warga, $module, $pages[0]);
    $service->markPageCompleted($warga, $module, $pages[1]);

    // Hapus halaman pertama yang sudah diselesaikan → ID-nya jadi hantu.
    $deletedId = $pages[0]->id;
    $pages[0]->delete();

    // Buka halaman ke-2 lagi → rekonsiliasi membuang ID hantu.
    $service->markPageCompleted($warga, $module, $pages[1]);

    $progress = UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)->first();
    expect($progress->pages_completed)->not->toContain($deletedId)
        ->and($progress->pages_completed)->toContain($pages[1]->id);
});

// ── #11 Prasyarat yang dihapus tidak mengunci ────────────────────────
it('prasyarat aktif yang belum diselesaikan tetap mengunci modul', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $prereq = makeModuleWithPages(1);
    $module = makeModuleWithPages(1);
    $module->update(['prerequisite_module_id' => $prereq->id]);

    expect(app(LmsProgressService::class)->isModuleAccessible($warga, $module))->toBeFalse();
});

it('prasyarat yang sudah dihapus tidak mengunci modul', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $prereq = makeModuleWithPages(1);
    $module = makeModuleWithPages(1);
    $module->update(['prerequisite_module_id' => $prereq->id]);

    $prereq->delete(); // soft delete

    expect(app(LmsProgressService::class)->isModuleAccessible($warga->refresh(), $module->refresh()))->toBeTrue();
});

// ── #2 Prasyarat lintas-nagari ditolak ───────────────────────────────
it('menolak prasyarat lintas-nagari untuk modul global', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $nagari = Nagari::factory()->create();
    $lokal = makeModuleWithPages(1, $nagari->id);

    Livewire::test(CreateModule::class)
        ->fillForm([
            'title' => 'Modul Global',
            'status' => 'draft',
            'nagari_id' => null,
            'prerequisite_module_id' => $lokal->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['prerequisite_module_id']);
});

it('mengizinkan prasyarat global untuk modul global', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $global = makeModuleWithPages(1);

    Livewire::test(CreateModule::class)
        ->fillForm([
            'title' => 'Modul Global 2',
            'status' => 'draft',
            'nagari_id' => null,
            'prerequisite_module_id' => $global->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

// ── #7/#8 Bersih-bersih file & kolom basi ────────────────────────────
it('mengosongkan kolom tak relevan saat tipe halaman berubah', function () {
    Storage::fake('public');
    $module = makeModuleWithPages(1);

    $page = ModulePage::create([
        'module_id' => $module->id, 'title' => 'PDF', 'type' => 'pdf',
        'file_path' => 'modules/pages/pdf/a.pdf', 'sort_order' => 5,
    ]);

    $page->update(['type' => 'text', 'content' => 'Sekarang teks']);

    expect($page->refresh()->file_path)->toBeNull()
        ->and($page->video_url)->toBeNull()
        ->and($page->content)->toBe('Sekarang teks');
});

it('menghapus file PDF dari disk saat halaman dihapus', function () {
    Storage::fake('public');
    Storage::disk('public')->put('modules/pages/pdf/b.pdf', 'dummy');
    $module = makeModuleWithPages(1);

    $page = ModulePage::create([
        'module_id' => $module->id, 'title' => 'PDF', 'type' => 'pdf',
        'file_path' => 'modules/pages/pdf/b.pdf', 'sort_order' => 5,
    ]);

    $page->delete();

    Storage::disk('public')->assertMissing('modules/pages/pdf/b.pdf');
});
