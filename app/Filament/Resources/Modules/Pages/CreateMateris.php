<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Modules\RelationManagers\MaterisRelationManager;
use App\Models\Materi;
use App\Services\SlcBatchAuthoringService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class CreateMateris extends Page
{
    use InteractsWithRecord;
    use HasUnsavedDataChangesAlert;

    protected static string $resource = ModuleResource::class;

    protected string $view = 'filament.resources.modules.pages.create-materis';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        $this->form->fill([
            'materis' => [
                ...$this->getRecord()
                    ->materis()
                    ->orderBy('urutan')
                    ->get(['id', 'judul', 'blocks'])
                    ->map(fn (Materi $materi): array => [
                        'id' => $materi->getKey(),
                        'judul' => $materi->judul,
                        'blocks' => $materi->blocks ?? [],
                    ])
                    ->all(),
                [],
            ],
        ]);
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    public function getTitle(): string
    {
        return 'Tambah Materi';
    }

    public function getSubheading(): string
    {
        return 'Modul: '.$this->getRecord()->judul;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model(Materi::class)
            ->operation('edit')
            ->statePath('data')
            ->components([
                Section::make('Daftar Materi')
                    ->description('Materi yang sudah ada ditampilkan lebih dahulu. Tambahkan materi baru di bagian bawah, lalu susun urutannya bila diperlukan. Warga membacanya berurutan dari atas.')
                    ->icon('heroicon-o-document-duplicate')
                    ->schema([
                        Repeater::make('materis')
                            ->hiddenLabel()
                            ->schema([
                                Hidden::make('id'),
                                ...MaterisRelationManager::fields(),
                            ])
                            ->itemLabel(fn (array $state): string => filled($state['judul'] ?? null)
                                ? (string) $state['judul']
                                : 'Materi baru')
                            ->defaultItems(0)
                            ->minItems(1)
                            ->addActionLabel('Tambah Materi Berikutnya')
                            ->reorderable()
                            ->collapsible()
                            // Materi tersimpan dihapus lewat halaman detail agar tidak
                            // terhapus tanpa sengaja saat tujuan halaman ini menambah.
                            ->deleteAction(fn (Action $action): Action => $action
                                ->visible(function (array $arguments, Repeater $component): bool {
                                    $items = $component->getRawState();

                                    return blank($items[$arguments['item']]['id'] ?? null);
                                }))
                            ->columnSpanFull(),
                    ]),

                FormActions::make([
                    Action::make('create')
                        ->label('Simpan')
                        ->submit('create'),
                    Action::make('cancel')
                        ->label('Batal')
                        ->color('gray')
                        ->url($this->cancelUrl()),
                ])
                    ->alignment(Alignment::End)
                    ->columnSpanFull(),
            ]);
    }

    public function create(): void
    {
        $this->authorizeAccess();
        $data = $this->form->getState();

        app(SlcBatchAuthoringService::class)->createMateris(
            $this->getRecord(),
            auth()->user(),
            $data['materis'] ?? [],
        );

        Notification::make()
            ->title('Materi berhasil disimpan')
            ->success()
            ->send();

        $this->redirect($this->cancelUrl(), navigate: true);
    }

    public function cancelUrl(): string
    {
        return ModuleResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    private function authorizeAccess(): void
    {
        $module = $this->getRecord();

        abort_unless(
            ! $module->trashed()
            && (auth()->user()?->can('update', $module) ?? false),
            403,
        );
    }
}
