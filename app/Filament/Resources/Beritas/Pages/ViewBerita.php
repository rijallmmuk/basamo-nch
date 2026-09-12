<?php

namespace App\Filament\Resources\Beritas\Pages;

use App\Filament\Resources\Beritas\BeritaResource;
use App\Models\Berita;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBerita extends ViewRecord
{
    protected static string $resource = BeritaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('lihatPublik')
                ->label('Lihat di Web')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(function (Berita $record): string {
                    if ($record->nagari) {
                        return route('public.nagari.kabar.detail.fallback', [
                            'nagari' => $record->nagari->slug,
                            'berita' => $record->slug,
                        ]);
                    }

                    return route('public.kabar.detail', ['berita' => $record->slug]);
                })
                ->openUrlInNewTab(),

            EditAction::make()
                ->color('warning')
                ->visible(fn (Berita $record): bool => auth()->user()?->can('update', $record) ?? false),

            DeleteAction::make()
                ->visible(fn (Berita $record): bool => auth()->user()?->can('delete', $record) ?? false),
        ];
    }
}
