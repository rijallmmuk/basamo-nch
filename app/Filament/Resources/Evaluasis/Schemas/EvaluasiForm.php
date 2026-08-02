<?php

namespace App\Filament\Resources\Evaluasis\Schemas;

use App\Enums\JenisEvaluasi;
use App\Models\Module;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Form dipakai bersama oleh menu Pre-test dan menu Evaluasi Kegiatan. Jenisnya
 * ditentukan menu, bukan dipilih pengguna, sehingga tidak ada field jenis sama sekali.
 */
class EvaluasiForm
{
    public static function configure(Schema $schema, JenisEvaluasi $jenis = JenisEvaluasi::Kegiatan): Schema
    {
        return $schema
            ->components([
                Section::make($jenis === JenisEvaluasi::Pretest ? 'Pengaturan Pre-test' : 'Pengaturan Evaluasi Kegiatan')
                    ->icon($jenis === JenisEvaluasi::Pretest
                        ? Heroicon::OutlinedClipboardDocumentList
                        : Heroicon::OutlinedClipboardDocumentCheck)
                    ->description($jenis === JenisEvaluasi::Pretest
                        ? 'Gerbang di awal modul: warga mengerjakannya satu kali sebelum materi terbuka. Tanpa nilai kelulusan dan tanpa batas percobaan, jadi kedua pengaturan itu tidak ada di sini.'
                        : 'Penutup modul: warga mengerjakannya setelah seluruh materi selesai.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        self::modulField($jenis),

                        ...($jenis === JenisEvaluasi::Pretest ? [] : self::penilaianFields()),
                    ]),

                Section::make('Daftar Soal')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->description('Susun satu atau beberapa soal sekaligus; soal masih dapat ditambahkan lagi setelah disimpan. Evaluasi otomatis terbuka bagi warga begitu setiap soalnya lengkap, tanpa perlu diterbitkan.')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->schema([
                        Repeater::make('pertanyaans')
                            ->hiddenLabel()
                            ->relationship('pertanyaans', fn ($query) => $query->orderBy('urutan'))
                            ->orderColumn('urutan')
                            ->schema(PertanyaanFields::make(withRelationships: true))
                            ->itemLabel(fn (array $state): string => filled($state['pertanyaan'] ?? null)
                                ? str((string) $state['pertanyaan'])->limit(70)
                                : 'Soal baru')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel('Tambah Soal Berikutnya')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function modulField(JenisEvaluasi $jenis): Select
    {
        return Select::make('module_id')
            ->label('Modul')
            ->placeholder('Pilih modul')
            ->relationship(
                name: 'module',
                titleAttribute: 'judul',
                // Satu modul boleh punya satu evaluasi aktif per jenis. Saat mengubah,
                // modul milik evaluasi ini sendiri tetap disertakan agar tidak hilang.
                modifyQueryUsing: function (Builder $query, ?Model $record) use ($jenis) {
                    $actor = auth()->user();
                    $query->with('pelatihan');

                    if ($actor) {
                        $query->manageableBy($actor);
                    } else {
                        $query->whereKey([]);
                    }

                    $query->where(function (Builder $q) use ($record, $jenis) {
                        $q->whereDoesntHave('evaluasis', fn (Builder $evaluasis) => $evaluasis
                            ->where('jenis', $jenis->value));

                        if ($record?->module_id) {
                            $q->orWhere('id', $record->module_id);
                        }
                    });
                },
            )
            ->searchable()
            ->preload()
            ->required()
            ->getOptionLabelFromRecordUsing(fn (Model $record): string => ($record->pelatihan?->temaNama() ?? 'Tanpa pelatihan')
                .' · '.$record->judul)
            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                $actor = auth()->user();

                if (! $actor || ! $value) {
                    return;
                }

                if (Module::query()->manageableBy($actor)->whereKey($value)->doesntExist()) {
                    $fail('Modul tidak valid atau berada di luar kewenangan Anda.');
                }
            })
            // Modul dikunci setelah dibuat karena evaluasi bisa sudah punya soal dan
            // percobaan warga. Untuk memindahkannya: hapus lalu buat ulang.
            ->disabled(fn (string $operation, $livewire): bool => $operation === 'edit'
                || (property_exists($livewire, 'lockedModuleId') && $livewire->lockedModuleId !== null))
            ->dehydrated(fn (string $operation): bool => $operation !== 'edit')
            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                ? 'Modul tidak dapat dipindahkan setelah soal atau pengerjaan tercatat.'
                : null)
            ->columnSpanFull();
    }

    /** @return list<TextInput> */
    private static function penilaianFields(): array
    {
        return [
            TextInput::make('nilai_lulus')
                ->label('Nilai Kelulusan')
                ->placeholder('Isi nilai kelulusan')
                ->numeric()
                ->default(70)
                ->minValue(1)
                ->maxValue(100)
                ->required()
                ->suffix('dari 100')
                ->helperText('Nilai minimum untuk dinyatakan lulus.')
                ->columnSpan(1),

            TextInput::make('maks_percobaan')
                ->label('Batas Percobaan')
                ->placeholder('Isi batas percobaan')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->maxValue(255)
                ->required()
                ->suffix('kali')
                ->helperText('Isi 0 untuk percobaan tanpa batas.')
                ->columnSpan(1),
        ];
    }
}
