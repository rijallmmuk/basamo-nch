<?php

use App\Models\Desa;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function makeModule(string $status = 'published'): Module
{
    return Module::create([
        'judul' => 'Modul '.uniqid(),
        'slug' => 'modul-'.uniqid(),
        'status' => $status,
        'urutan' => 1,
    ]);
}

// ── Q1: Quiz SoftDeletes ─────────────────────────────────────────────
it('Q1: hapus kuis = soft delete (arsip) dan bisa dipulihkan', function () {
    $quiz = Quiz::create(['module_id' => makeModule()->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);

    $quiz->delete();
    expect(Quiz::find($quiz->id))->toBeNull()
        ->and(Quiz::withTrashed()->find($quiz->id)->trashed())->toBeTrue();

    $quiz->restore();
    expect(Quiz::find($quiz->id))->not->toBeNull();
});

it('Q1: modul bisa diberi kuis baru setelah kuis lama diarsipkan', function () {
    $module = makeModule();
    Quiz::create(['module_id' => $module->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3])->delete();

    // Tak melanggar unique (module_id, deleted_at).
    Quiz::create(['module_id' => $module->id, 'nilai_lulus' => 80, 'maks_percobaan' => 1]);

    expect(Quiz::where('module_id', $module->id)->count())->toBe(1)               // aktif
        ->and(Quiz::withTrashed()->where('module_id', $module->id)->count())->toBe(2);
});

// ── M1: force-delete modul membersihkan file PDF halaman ─────────────
it('M1: hapus permanen modul menghapus file PDF halamannya', function () {
    $disk = config('media-library.disk_name');
    Storage::fake($disk);
    Storage::disk($disk)->put('modules/pages/pdf/a.pdf', '%PDF-1.4');

    $module = makeModule();
    ModulePage::create([
        'module_id' => $module->id, 'judul' => 'PDF', 'tipe' => 'pdf',
        'path_file' => 'modules/pages/pdf/a.pdf', 'urutan' => 1,
    ]);

    $module->forceDelete();

    Storage::disk($disk)->assertMissing('modules/pages/pdf/a.pdf');
});

// ── M3: URL video — render hanya http/https (defense-in-depth) ────────
it('M3: URL video skema javascript: tidak dirender sebagai tautan', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    $module = makeModule(); // global, published
    ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Video', 'tipe' => 'video',
        'url_video' => 'javascript:alert(1)', 'urutan' => 1,
    ]);
    $page = $module->pages()->first();

    actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk()
        ->assertDontSee('javascript:alert(1)', false);
});

it('M3: URL video YouTube https dirender sebagai iframe embed', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    $module = makeModule();
    ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Video', 'tipe' => 'video',
        'url_video' => 'https://www.youtube.com/watch?v=abcdefghijk', 'urutan' => 1,
    ]);
    $page = $module->pages()->first();

    actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk()
        ->assertSee('youtube.com/embed/abcdefghijk', false);
});

// ── Penjelasan/instruksi opsional untuk video & PDF ──────────────────
it('halaman video bisa punya teks penjelasan opsional (tersimpan & tampil)', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    $module = makeModule();
    $page = ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Video', 'tipe' => 'video',
        'url_video' => 'https://youtu.be/abcdefghijk',
        'konten' => '<p>Tonton lalu catat poin penting.</p>',
        'urutan' => 1,
    ]);

    // konten dipertahankan untuk video (tidak di-null saving hook).
    expect($page->refresh()->konten)->toContain('catat poin penting');

    actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk()
        ->assertSee('catat poin penting');
});

it('saving membersihkan media silang tapi mempertahankan konten', function () {
    $page = ModulePage::create([
        'module_id' => makeModule()->id, 'judul' => 'V', 'tipe' => 'video',
        'url_video' => 'https://youtu.be/abcdefghijk', 'path_file' => 'lama.pdf',
        'konten' => '<p>nota</p>', 'urutan' => 1,
    ]);

    expect($page->refresh())
        ->path_file->toBeNull()       // bukan pdf → dibersihkan
        ->and($page->url_video)->not->toBeNull()
        ->and($page->konten)->toContain('nota');
});
