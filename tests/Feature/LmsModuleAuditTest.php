<?php

use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Models\Desa;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Services\LmsPointService;
use App\Services\LmsProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/** Modul published + N halaman, tiap halaman satu blok teks. */
function makeModuleWithPages(int $pageCount, ?int $desaId = null): Module
{
    $module = Module::create([
        'judul' => 'Modul '.uniqid(),
        'slug' => 'modul-'.uniqid(),
        'status' => 'published',
        'urutan' => 1,
        'desa_id' => $desaId,
    ]);

    for ($i = 1; $i <= $pageCount; $i++) {
        ModulePage::create([
            'module_id' => $module->id,
            'judul' => "Materi $i",
            'blocks' => [['type' => 'teks', 'data' => ['konten' => "Isi materi $i."]]],
            'urutan' => $i,
        ]);
    }

    return $module;
}

// ── #1 Keamanan: XSS pada konten materi ──────────────────────────────
it('konten materi disanitasi dari script saat ditampilkan ke warga', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);

    $module = Module::create([
        'judul' => 'Modul XSS', 'slug' => 'modul-xss', 'status' => 'published', 'urutan' => 1,
    ]);
    $page = ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Materi',
        'blocks' => [['type' => 'teks', 'data' => ['konten' => '<script>alert(1)</script><p>Konten aman</p>']]],
        'urutan' => 1,
    ]);

    $this->actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk()
        ->assertSee('Konten aman')
        ->assertDontSee('alert(1)');
});

// ── Penyelesaian materi = EKSPLISIT (tombol "Tandai selesai", POST) ──
it('membuka halaman materi (GET) tidak lagi menandai selesai', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeModuleWithPages(2, $desa->id);
    $page = $module->pages()->orderBy('urutan')->first();

    $this->actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk();

    expect(UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)->exists())->toBeFalse();
});

it('POST tandai-selesai menandai halaman & mengarahkan ke halaman berikutnya', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeModuleWithPages(2, $desa->id);
    $pages = $module->pages()->orderBy('urutan')->get();

    $this->actingAs($warga)
        ->post(route('portal.modules.pages.complete', [$module, $pages[0]]))
        ->assertRedirect(route('portal.modules.pages.show', [$module, $pages[1]]));

    $progress = UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)->first();
    expect($progress->halaman_selesai)->toContain($pages[0]->id)
        ->and($progress->status->value)->toBe('in_progress');
});

it('menandai halaman terakhir menyelesaikan modul + beri XP, arahkan ke modul', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeModuleWithPages(2, $desa->id);
    $pages = $module->pages()->orderBy('urutan')->get();

    app(LmsProgressService::class)->markPageCompleted($warga, $module, $pages[0]);

    $this->actingAs($warga)
        ->post(route('portal.modules.pages.complete', [$module, $pages[1]]))
        ->assertRedirect(route('portal.modules.show', $module));

    $progress = UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)->first();
    expect($progress->status->value)->toBe('completed')
        ->and($warga->refresh()->total_xp)->toBe(LmsPointService::MODULE_XP);
});

// ── #3/#4 Penyelesaian: rekonsiliasi setelah halaman dihapus ──────────
it('menyelesaikan modul setelah halaman tersisa dibuka semua, walau ada halaman dihapus', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeModuleWithPages(4);
    $pages = $module->pages()->orderBy('urutan')->get();

    $service = app(LmsProgressService::class);

    // Buka 3 dari 4 halaman → belum selesai.
    $service->markPageCompleted($warga, $module, $pages[0]);
    $service->markPageCompleted($warga, $module, $pages[1]);
    $service->markPageCompleted($warga, $module, $pages[2]);

    $progress = UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)->first();
    expect($progress->status->value)->toBe('in_progress');

    // Admin menghapus halaman ke-4 (yang belum dibuka).
    $pages[3]->delete();

    // Warga membuka ulang halaman yang sudah selesai → harus memicu rekonsiliasi.
    $service->markPageCompleted($warga, $module, $pages[0]);

    $progress->refresh();
    expect($progress->status->value)->toBe('completed')
        ->and($progress->completed_at)->not->toBeNull()
        ->and($warga->refresh()->total_xp)->toBe(50);
});

it('membuang ID halaman hantu dari halaman_selesai', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $module = makeModuleWithPages(3);
    $pages = $module->pages()->orderBy('urutan')->get();
    $service = app(LmsProgressService::class);

    $service->markPageCompleted($warga, $module, $pages[0]);
    $service->markPageCompleted($warga, $module, $pages[1]);

    // Hapus halaman pertama yang sudah diselesaikan → ID-nya jadi hantu.
    $deletedId = $pages[0]->id;
    $pages[0]->delete();

    // Buka halaman ke-2 lagi → rekonsiliasi membuang ID hantu.
    $service->markPageCompleted($warga, $module, $pages[1]);

    $progress = UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)->first();
    expect($progress->halaman_selesai)->not->toContain($deletedId)
        ->and($progress->halaman_selesai)->toContain($pages[1]->id);
});

// ── #11 Prasyarat yang dihapus tidak mengunci ────────────────────────
it('prasyarat aktif yang belum diselesaikan tetap mengunci modul', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $prereq = makeModuleWithPages(1);
    $module = makeModuleWithPages(1);
    $module->update(['prasyarat_module_id' => $prereq->id]);

    expect(app(LmsProgressService::class)->isModuleAccessible($warga, $module))->toBeFalse();
});

it('prasyarat yang sudah dihapus tidak mengunci modul', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $prereq = makeModuleWithPages(1);
    $module = makeModuleWithPages(1);
    $module->update(['prasyarat_module_id' => $prereq->id]);

    $prereq->delete(); // soft delete

    expect(app(LmsProgressService::class)->isModuleAccessible($warga->refresh(), $module->refresh()))->toBeTrue();
});

// ── #2 Prasyarat lintas-desa ditolak ───────────────────────────────
it('menolak prasyarat lintas-desa untuk modul global', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();
    $lokal = makeModuleWithPages(1, $desa->id);

    Livewire::test(CreateModule::class)
        ->fillForm([
            'judul' => 'Modul Global',
            'status' => 'draft',
            'desa_id' => null,
            'prasyarat_module_id' => $lokal->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['prasyarat_module_id']);
});

it('mengizinkan prasyarat global untuk modul global', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $global = makeModuleWithPages(1);

    Livewire::test(CreateModule::class)
        ->fillForm([
            'judul' => 'Modul Global 2',
            'status' => 'draft',
            'desa_id' => null,
            'prasyarat_module_id' => $global->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

// ── #7/#8 Bersih-bersih berkas blok ──────────────────────────────────
it('menghapus berkas blok yang dibuang saat halaman diperbarui', function () {
    $disk = config('media-library.disk_name');
    Storage::fake($disk);
    Storage::disk($disk)->put('modules/blocks/pdf/a.pdf', '%PDF-1.4');

    $module = makeModuleWithPages(1);
    $page = ModulePage::create([
        'module_id' => $module->id, 'judul' => 'PDF', 'urutan' => 5,
        'blocks' => [['type' => 'pdf', 'data' => ['file' => 'modules/blocks/pdf/a.pdf', 'judul' => null]]],
    ]);

    // Ganti isi halaman jadi teks saja → blok PDF (beserta berkasnya) dibuang.
    $page->update(['blocks' => [['type' => 'teks', 'data' => ['konten' => 'Sekarang teks']]]]);

    Storage::disk($disk)->assertMissing('modules/blocks/pdf/a.pdf');
});

it('menghapus semua berkas blok dari disk saat halaman dihapus', function () {
    $disk = config('media-library.disk_name');
    Storage::fake($disk);
    Storage::disk($disk)->put('modules/blocks/pdf/b.pdf', 'dummy');
    Storage::disk($disk)->put('modules/blocks/gambar/c.jpg', 'dummy');

    $module = makeModuleWithPages(1);
    $page = ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Campuran', 'urutan' => 5,
        'blocks' => [
            ['type' => 'pdf', 'data' => ['file' => 'modules/blocks/pdf/b.pdf']],
            ['type' => 'gambar', 'data' => ['file' => 'modules/blocks/gambar/c.jpg']],
        ],
    ]);

    $page->delete();

    Storage::disk($disk)->assertMissing('modules/blocks/pdf/b.pdf');
    Storage::disk($disk)->assertMissing('modules/blocks/gambar/c.jpg');
});

// ── Pindah desa modul (edit): guard slug bentrok + prasyarat lintas-desa ──
it('memindah desa modul ditolak bila slug bentrok di ruang tujuan', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();

    $global = Module::create(['judul' => 'Modul Kembar', 'status' => 'draft']);
    Module::create(['judul' => 'Modul Kembar', 'status' => 'draft', 'desa_id' => $desa->id]);

    Livewire::test(EditModule::class, ['record' => $global->getRouteKey()])
        ->fillForm(['desa_id' => $desa->id])
        ->call('save')
        ->assertHasFormErrors(['desa_id']);

    expect($global->fresh()->desa_id)->toBeNull();
});

it('memindah modul prasyarat keluar jangkauan modul dependennya ditolak', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();

    $prasyarat = Module::create(['judul' => 'Dasar', 'status' => 'draft']); // global
    Module::create(['judul' => 'Lanjutan', 'status' => 'draft', 'desa_id' => $desaB->id, 'prasyarat_module_id' => $prasyarat->id]);

    // Global → desa A: modul desa B yang bergantung padanya akan terkunci permanen.
    Livewire::test(EditModule::class, ['record' => $prasyarat->getRouteKey()])
        ->fillForm(['desa_id' => $desaA->id])
        ->call('save')
        ->assertHasFormErrors(['desa_id']);

    expect($prasyarat->fresh()->desa_id)->toBeNull();
});

it('memindah desa modul tetap bisa bila tak ada bentrokan', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();
    $module = Module::create(['judul' => 'Bebas Pindah', 'status' => 'draft']);

    Livewire::test(EditModule::class, ['record' => $module->getRouteKey()])
        ->fillForm(['desa_id' => $desa->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($module->fresh()->desa_id)->toBe($desa->id);
});
