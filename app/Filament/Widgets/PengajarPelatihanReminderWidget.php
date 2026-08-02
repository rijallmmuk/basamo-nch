<?php

namespace App\Filament\Widgets;

use App\Enums\StatusPelatihan;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use App\Models\Pelatihan;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class PengajarPelatihanReminderWidget extends Widget
{
    protected string $view = 'filament.widgets.pengajar-pelatihan-reminder';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user?->hasAnyRole(['superadmin', 'operator', 'pengajar'])) {
            return false;
        }

        return self::manageableBy($user)
            ->where('status', StatusPelatihan::Terkunci)
            ->ready()
            ->exists();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return ['siapDibuka' => collect(), 'pelatihanUrl' => '#'];
        }

        $siapDibuka = self::manageableBy($user)
            ->with(['tema', 'nagaris'])
            ->where('status', StatusPelatihan::Terkunci)
            ->ready()
            ->latest('updated_at')
            ->get();

        return [
            'siapDibuka' => $siapDibuka,
            'pelatihanUrl' => PelatihanResource::getUrl('index'),
        ];
    }

    /** @return Builder<Pelatihan> */
    private static function manageableBy(User $user): Builder
    {
        return Pelatihan::query()->manageableBy($user);
    }
}
