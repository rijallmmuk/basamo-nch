<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Models\EwsDevice;
use Filament\Widgets\Widget;

class OperatorEwsWidget extends Widget
{
    use ScopedToNagari;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.operator-ews';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $nagari = $this->nagari();

        $device = $nagari ? EwsDevice::siapPakai()->where('nagari_id', $nagari->id)->with('pembacaanTerakhir')->first() : null;

        return [
            'nagari' => $nagari,
            'device' => $device,
        ];
    }
}
