<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductionHealthService
{
    public function check(): void
    {
        if (! config('production.health.enabled')) {
            return;
        }

        $checks = [
            'database' => fn () => DB::connection()->select('select 1'),
            'cache' => fn () => $this->checkCache(),
            'storage' => fn () => $this->checkStorage(),
            'queue' => fn () => Queue::connection()->size(),
        ];

        $failures = [];

        foreach ($checks as $name => $check) {
            if (! config("production.health.checks.{$name}", true)) {
                continue;
            }

            try {
                $check();
            } catch (Throwable $exception) {
                $failures[$name] = $exception->getMessage();
            }
        }

        if ($failures !== []) {
            throw new RuntimeException('Pemeriksaan kesehatan gagal: '.implode(', ', array_keys($failures)));
        }
    }

    private function checkCache(): void
    {
        $key = 'health:'.Str::uuid();
        $value = Str::random();

        try {
            if (! Cache::put($key, $value, 30) || Cache::get($key) !== $value) {
                throw new RuntimeException('Cache tidak dapat ditulis atau dibaca.');
            }
        } finally {
            Cache::forget($key);
        }
    }

    private function checkStorage(): void
    {
        foreach (config('production.health.storage_disks', []) as $diskName) {
            $disk = Storage::disk($diskName);
            $path = '.health/'.Str::uuid();
            $value = Str::random();

            try {
                if (! $disk->put($path, $value) || $disk->get($path) !== $value) {
                    throw new RuntimeException("Disk {$diskName} tidak dapat ditulis atau dibaca.");
                }
            } finally {
                $disk->delete($path);
            }
        }
    }
}
