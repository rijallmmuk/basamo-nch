<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('root menampilkan landing page publik', function () {
    $this->get('/')->assertOk()->assertSee('Basamo NCH');
});

test('halaman login portal dapat diakses tamu', function () {
    $this->get(route('login'))->assertSuccessful();
});
