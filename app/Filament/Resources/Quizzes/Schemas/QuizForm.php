<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Models\Module;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengaturan Kuis')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('module_id')
                            ->label('Modul')
                            ->relationship(
                                name: 'module',
                                titleAttribute: 'judul',
                                // Hanya tampilkan modul yang BELUM punya kuis (1 modul = 1 kuis).
                                // Saat edit, tetap sertakan modul kuis ini sendiri.
                                modifyQueryUsing: function (Builder $query, ?Model $record) {
                                    $query->where(function (Builder $q) use ($record) {
                                        $q->whereDoesntHave('quiz');
                                        if ($record?->module_id) {
                                            $q->orWhere('id', $record->module_id);
                                        }
                                    });

                                    // desa_admin hanya boleh membuat kuis untuk modul desanya.
                                    if (auth()->user()?->isDesaAdmin()) {
                                        $query->where('desa_id', auth()->user()->desa_id);
                                    }
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            // Modul kuis dikunci setelah dibuat: 1 modul = 1 kuis, dan kuis bisa
                            // sudah punya soal/percobaan warga. Untuk memindah → hapus & buat ulang.
                            // Field disabled tak terdehidrasi → module_id tak berubah saat simpan.
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'Modul tidak bisa diubah setelah kuis dibuat.'
                                : null)
                            // Pertahanan server-side (modifyQueryUsing hanya membatasi opsi yang tampil):
                            // desa_admin tak boleh menempelkan kuis ke modul desa lain / modul global.
                            ->rule(fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                $user = auth()->user();

                                if (! $user?->isDesaAdmin()) {
                                    return;
                                }

                                $module = Module::find($value);

                                if (! $module || $module->desa_id !== $user->desa_id) {
                                    $fail('Modul tidak valid untuk desa Anda.');
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('nilai_lulus')
                            ->label('Nilai Kelulusan')
                            ->helperText('Nilai minimum untuk lulus (skala 0–100).')
                            ->numeric()
                            ->default(70)
                            ->minValue(1)
                            ->maxValue(100)
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('maks_percobaan')
                            ->label('Maks. Percobaan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(255)
                            ->helperText('Isi 0 untuk percobaan tidak terbatas (default).')
                            ->required()
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
