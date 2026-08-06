<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Services\BmkgWeatherService;
use Filament\Widgets\Widget;

class OperatorCuacaWidget extends Widget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.operator-cuaca';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $nagari = $this->nagari();

        $cuaca = $nagari ? app(BmkgWeatherService::class)->prakiraan($nagari) : null;

        return [
            'nagari' => $nagari,
            'cuaca' => $cuaca,
        ];
    }
}
