<?php

namespace App\Console\Commands;

use App\Services\ProductionReadinessService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ops:production-check')]
#[Description('Validasi konfigurasi wajib sebelum aplikasi menerima trafik production')]
class ProductionPreflightCommand extends Command
{
    public function handle(ProductionReadinessService $readiness): int
    {
        $checks = $readiness->checks();

        $this->table(
            ['Pemeriksaan', 'Status', 'Tindakan'],
            array_map(fn (array $check): array => [
                $check['label'],
                $check['passed'] ? 'LULUS' : 'GAGAL',
                $check['passed'] ? '-' : $check['message'],
            ], $checks),
        );

        if (collect($checks)->contains(fn (array $check): bool => ! $check['passed'])) {
            $this->error('Production preflight gagal. Jangan arahkan trafik ke release ini.');

            return self::FAILURE;
        }

        $this->info('Semua production preflight lulus.');

        return self::SUCCESS;
    }
}
