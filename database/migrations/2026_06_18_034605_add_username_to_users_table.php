<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill username untuk user yang sudah ada (dari bagian lokal email),
        // dijamin unik agar login lama tetap bisa pakai email maupun username.
        $used = [];

        foreach (DB::table('users')->select('id', 'email')->get() as $user) {
            $base = Str::of($user->email)->before('@')->slug('_')->value() ?: 'user';
            $username = $base;
            $suffix = 1;

            while (in_array($username, $used, true) || DB::table('users')->where('username', $username)->exists()) {
                $username = $base.'_'.(++$suffix);
            }

            $used[] = $username;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
