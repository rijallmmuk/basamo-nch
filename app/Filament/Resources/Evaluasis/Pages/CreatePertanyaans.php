<?php

namespace App\Filament\Resources\Evaluasis\Pages;

use App\Filament\Resources\Evaluasis\Schemas\PertanyaanFields;
use App\Models\Evaluasi;
use App\Models\EvaluasiPertanyaan;
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

abstract class CreatePertanyaans extends Page
{
    use InteractsWithRecord;
    use HasUnsavedDataChangesAlert;

    protected string $view = 'filament.resources.evaluasis.pages.create-pertanyaans';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        $this->form->fill([
            'pertanyaans' => [
                ...$this->getRecord()
                    ->pertanyaans()
                    ->with('opsis')
                    ->orderBy('urutan')
                    ->get()
                    ->map(fn (EvaluasiPertanyaan $pertanyaan): array => [
                        'id' => $pertanyaan->getKey(),
                        'pertanyaan' => $pertanyaan->pertanyaan,
                        'opsis' => $pertanyaan->opsis
                            ->map(fn ($opsi): array => [
                                'id' => $opsi->getKey(),
                                'teks_opsi' => $opsi->teks_opsi,
                                'is_correct' => $opsi->is_correct,
                            ])
                            ->all(),
                    ])
                    ->all(),
                [
                    'opsis' => [[], []],
                ],
            ],
        ]);
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    public function getTitle(): string
    {
        return 'Tambah Soal '.$this->getRecord()->jenis->getLabel();
    }

    public function getSubheading(): string
    {
        return 'Modul: '.($this->getRecord()->module?->judul ?? '—');
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model(EvaluasiPertanyaan::class)
            ->operation('edit')
            ->statePath('data')
            ->components([
                Section::make('Daftar Soal')
                    ->description('Soal yang sudah ada ditampilkan lebih dahulu. Tambahkan soal baru di bagian bawah, lalu susun urutannya bila diperlukan. Evaluasi otomatis terbuka bagi warga begitu setiap soalnya lengkap.')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        Repeater::make('pertanyaans')
                            ->hiddenLabel()
                            ->schema([
                                Hidden::make('id'),
                                ...PertanyaanFields::make(withIds: true),
                            ])
                            ->itemLabel(fn (array $state): string => filled($state['pertanyaan'] ?? null)
                                ? str((string) $state['pertanyaan'])->limit(70)
                                : 'Soal baru')
                            ->defaultItems(0)
                            ->minItems(1)
                            ->addActionLabel('Tambah Soal Berikutnya')
                            ->reorderable()
                            ->collapsible()
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

        app(SlcBatchAuthoringService::class)->createPertanyaans(
            $this->getRecord(),
            auth()->user(),
            $data['pertanyaans'] ?? [],
        );

        Notification::make()
            ->title('Soal berhasil disimpan')
            ->success()
            ->send();

        $this->redirect($this->cancelUrl(), navigate: true);
    }

    public function cancelUrl(): string
    {
        $resource = static::getResource();

        return $resource::getUrl('view', ['record' => $this->getRecord()]);
    }

    private function authorizeAccess(): void
    {
        /** @var Evaluasi $evaluasi */
        $evaluasi = $this->getRecord();

        abort_unless(
            ! $evaluasi->trashed()
            && (auth()->user()?->can('update', $evaluasi) ?? false),
            403,
        );
    }
}
