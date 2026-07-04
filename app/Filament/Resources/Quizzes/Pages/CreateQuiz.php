<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    /**
     * Setelah kuis dibuat, arahkan ke Edit (bukan index) — kuis baru belum bersoal,
     * dan di halaman Edit-lah admin menambah soal (relation manager). Edit kuis sendiri
     * tetap kembali ke index lewat trait RedirectsToIndex.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    /**
     * Prefill modul saat datang dari aksi "Tambah Kuis" di daftar modul
     * (QuizResource::getUrl('create', ['module_id' => …])). parent::mount() lebih dulu
     * mengisi default (nilai_lulus/maks_percobaan), lalu module_id diset di atasnya.
     */
    public function mount(): void
    {
        parent::mount();

        if ($moduleId = request()->integer('module_id')) {
            $this->data['module_id'] = $moduleId;
        }
    }
}
