<?php

test('root mengarahkan ke halaman login portal', function () {
    $this->get('/')->assertRedirect(route('portal.login'));
});

test('halaman login portal dapat diakses tamu', function () {
    $this->get(route('portal.login'))->assertSuccessful();
});
