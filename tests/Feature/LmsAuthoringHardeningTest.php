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

// ── M1: force-delete modul membersihkan berkas blok halaman ──────────
it('M1: hapus permanen modul menghapus berkas blok halamannya', function () {
    $disk = config('media-library.disk_name');
    Storage::fake($disk);
    Storage::disk($disk)->put('modules/blocks/pdf/a.pdf', '%PDF-1.4');

    $module = makeModule();
    ModulePage::create([
        'module_id' => $module->id, 'judul' => 'PDF', 'urutan' => 1,
        'blocks' => [['type' => 'pdf', 'data' => ['file' => 'modules/blocks/pdf/a.pdf']]],
    ]);

    $module->forceDelete();

    Storage::disk($disk)->assertMissing('modules/blocks/pdf/a.pdf');
});

it('M1: mengganti berkas blok saat edit menghapus berkas lama, menyimpan yang baru', function () {
    $disk = config('media-library.disk_name');
    Storage::fake($disk);
    Storage::disk($disk)->put('modules/blocks/pdf/lama.pdf', '%PDF-1.4');
    Storage::disk($disk)->put('modules/blocks/pdf/baru.pdf', '%PDF-1.4');

    $page = ModulePage::create([
        'module_id' => makeModule()->id, 'judul' => 'PDF', 'urutan' => 1,
        'blocks' => [['type' => 'pdf', 'data' => ['file' => 'modules/blocks/pdf/lama.pdf']]],
    ]);

    $page->update(['blocks' => [['type' => 'pdf', 'data' => ['file' => 'modules/blocks/pdf/baru.pdf']]]]);

    Storage::disk($disk)->assertMissing('modules/blocks/pdf/lama.pdf');
    Storage::disk($disk)->assertExists('modules/blocks/pdf/baru.pdf');
});

it('M1: membuang blok berkas saat edit menghapus berkasnya', function () {
    $disk = config('media-library.disk_name');
    Storage::fake($disk);
    Storage::disk($disk)->put('modules/blocks/gambar/x.jpg', 'JPG');

    $page = ModulePage::create([
        'module_id' => makeModule()->id, 'judul' => 'Gambar', 'urutan' => 1,
        'blocks' => [['type' => 'gambar', 'data' => ['file' => 'modules/blocks/gambar/x.jpg']]],
    ]);

    // Ganti jadi blok teks (buang blok gambar) → berkas gambar harus terhapus.
    $page->update(['blocks' => [['type' => 'teks', 'data' => ['konten' => 'Hanya teks.']]]]);

    Storage::disk($disk)->assertMissing('modules/blocks/gambar/x.jpg');
});

it('M1: menghapus satu halaman menghapus berkas bloknya', function () {
    $disk = config('media-library.disk_name');
    Storage::fake($disk);
    Storage::disk($disk)->put('modules/blocks/audio/a.mp3', 'MP3');

    $page = ModulePage::create([
        'module_id' => makeModule()->id, 'judul' => 'Audio', 'urutan' => 1,
        'blocks' => [['type' => 'audio', 'data' => ['file' => 'modules/blocks/audio/a.mp3']]],
    ]);

    $page->delete();

    Storage::disk($disk)->assertMissing('modules/blocks/audio/a.mp3');
});

// ── M3: URL video — render hanya http/https (defense-in-depth) ────────
it('M3: URL video skema javascript: tidak dirender sebagai tautan', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    $module = makeModule(); // global, published
    ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Video', 'urutan' => 1,
        'blocks' => [['type' => 'video', 'data' => ['url' => 'javascript:alert(1)']]],
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
        'module_id' => $module->id, 'judul' => 'Video', 'urutan' => 1,
        'blocks' => [['type' => 'video', 'data' => ['url' => 'https://www.youtube.com/watch?v=abcdefghijk']]],
    ]);
    $page = $module->pages()->first();

    actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk()
        ->assertSee('youtube.com/embed/abcdefghijk', false);
});

// ── Halaman campuran: teks + video dalam satu halaman ────────────────
it('halaman bisa mencampur blok teks + video (tersimpan & tampil berurut)', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    $module = makeModule();
    $page = ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Campuran', 'urutan' => 1,
        'blocks' => [
            ['type' => 'teks', 'data' => ['konten' => '<p>Tonton lalu catat poin penting.</p>']],
            ['type' => 'video', 'data' => ['url' => 'https://youtu.be/abcdefghijk', 'caption' => null]],
        ],
    ]);

    expect($page->refresh()->blocks)->toHaveCount(2)
        ->and($page->blocks[0]['type'])->toBe('teks')
        ->and($page->blocks[1]['type'])->toBe('video');

    actingAs($warga)
        ->get(route('portal.modules.pages.show', [$module, $page]))
        ->assertOk()
        ->assertSee('catat poin penting')
        ->assertSee('youtube.com/embed/abcdefghijk', false);
});

it('halaman bisa memuat seluruh tipe blok sekaligus (urutan dipertahankan)', function () {
    $page = ModulePage::create([
        'module_id' => makeModule()->id, 'judul' => 'Lengkap', 'urutan' => 1,
        'blocks' => [
            ['type' => 'teks', 'data' => ['konten' => '<p>intro</p>']],
            ['type' => 'video', 'data' => ['url' => 'https://youtu.be/abcdefghijk']],
            ['type' => 'pdf', 'data' => ['file' => 'modules/blocks/pdf/x.pdf']],
            ['type' => 'gambar', 'data' => ['file' => 'modules/blocks/gambar/x.jpg']],
            ['type' => 'audio', 'data' => ['file' => 'modules/blocks/audio/x.mp3']],
            ['type' => 'lampiran', 'data' => ['file' => 'modules/blocks/lampiran/x.docx']],
        ],
    ]);

    expect(collect($page->refresh()->blocks)->pluck('type')->all())
        ->toBe(['teks', 'video', 'pdf', 'gambar', 'audio', 'lampiran']);
});
