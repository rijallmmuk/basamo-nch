<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModuleBlockType;
use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Module;
use App\Services\SlcProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModuleFileController extends Controller
{
    public function __construct(private readonly SlcProgressService $progressService) {}

    public function __invoke(
        Request $request,
        Module $module,
        Materi $materi,
        int $block,
    ): StreamedResponse {
        $user = $request->user();

        abort_unless($user && $module->isAccessibleToWarga($user), 404);
        abort_unless($materi->module_id === $module->getKey(), 404);
        abort_unless($this->progressService->isModuleAccessible($user, $module), 404);

        if (! $user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])) {
            $progress = $this->progressService->getProgress($user, $module);
            abort_unless($this->progressService->isMateriAccessible($module, $materi, $progress?->halaman_selesai ?? []), 404);
        }

        $blockData = ($materi->blocks ?? [])[$block] ?? null;
        abort_unless(is_array($blockData), 404);

        $rawType = $blockData['type'] ?? null;
        $data = $blockData['data'] ?? null;
        abort_unless(is_string($rawType) && is_array($data), 404);

        $type = ModuleBlockType::tryFrom($rawType);
        $path = $data['file'] ?? null;

        abort_unless($type?->storesFile() && is_string($path), 404);
        abort_unless(str_starts_with($path, 'modules/blocks/') && !str_contains($path, '../'), 404);

        $disk = Storage::disk(config('slc.material_disk'));
        abort_unless($disk->exists($path), 404);

        $download = $request->boolean('download') || $type === ModuleBlockType::Lampiran;
        $name = $this->downloadName($data, $path);

        return $download
            ? $disk->download($path, $name)
            : $disk->response($path, $name, [
                'Content-Disposition' => 'inline; filename="'.$name.'"',
                'X-Content-Type-Options' => 'nosniff',
                /* `private` menahan berkas dari cache bersama mana pun; hanya peramban
                   warga sendiri yang boleh menyimpannya, dan cuma sepuluh menit. Tanpa
                   ini tiap kunjungan ulang ke materi yang sama mengunduh dokumennya dari
                   nol, padahal warga kerap bolak-balik antar materi. */
                'Cache-Control' => 'private, max-age=600',
            ]);
    }

    /** @param array<string, mixed> $data */
    private function downloadName(array $data, string $path): string
    {
        $label = $data['label'] ?? $data['judul'] ?? basename($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $base = pathinfo(basename((string) $label), PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?: 'materi';

        return $safeBase.($extension === '' ? '' : '.'.$extension);
    }
}
