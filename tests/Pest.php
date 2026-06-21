<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** Lewati uji geometri bila driver tak punya fungsi spasial (mis. SQLite). */
function skipUnlessSpatial(): void
{
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        test()->markTestSkipped('Butuh MySQL/MariaDB (ST_AsGeoJSON/ST_GeomFromText/ST_Contains).');
    }
}

/** Sisipkan satu baris geometri ke wilayah_boundaries (WKT → ST_GeomFromText). */
function seedBoundary(string $kode, int $level, string $nama, string $wkt, ?string $parent = null): void
{
    DB::insert(
        'INSERT INTO wilayah_boundaries (kode, level, parent_kode, nama, geom, geom_simplified) '
        .'VALUES (?,?,?,?,ST_GeomFromText(?),ST_GeomFromText(?))',
        [$kode, $level, $parent, $nama, $wkt, $wkt]
    );
}
