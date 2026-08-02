<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        if (! Schema::hasColumn('activity_log', 'attribute_changes')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->json('attribute_changes')->nullable()->after('causer_id');
            });
        }

        DB::table('activity_log')
            ->whereNotNull('properties')
            ->orderBy('id')
            ->eachById(function (object $activity): void {
                $properties = json_decode((string) $activity->properties, true);

                if (! is_array($properties)) {
                    return;
                }

                $changes = array_intersect_key($properties, array_flip(['attributes', 'old']));

                if ($changes === []) {
                    return;
                }

                $remaining = array_diff_key($properties, array_flip(['attributes', 'old']));

                DB::table('activity_log')->where('id', $activity->id)->update([
                    'attribute_changes' => json_encode($changes, JSON_UNESCAPED_UNICODE),
                    'properties' => $remaining === [] ? null : json_encode($remaining, JSON_UNESCAPED_UNICODE),
                ]);
            });

        if (Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->dropColumn('batch_uuid');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->uuid('batch_uuid')->nullable();
            });
        }

        if (! Schema::hasColumn('activity_log', 'attribute_changes')) {
            return;
        }

        DB::table('activity_log')
            ->whereNotNull('attribute_changes')
            ->orderBy('id')
            ->eachById(function (object $activity): void {
                $properties = json_decode((string) $activity->properties, true);
                $changes = json_decode((string) $activity->attribute_changes, true);

                DB::table('activity_log')->where('id', $activity->id)->update([
                    'properties' => json_encode(array_merge(
                        is_array($properties) ? $properties : [],
                        is_array($changes) ? $changes : [],
                    ), JSON_UNESCAPED_UNICODE),
                ]);
            });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn('attribute_changes');
        });
    }
};
