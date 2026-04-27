<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'auto_rescheduled_allowed')) {
                $table->boolean('auto_rescheduled_allowed')->default(0);
            }
            if (!Schema::hasColumn('doctors', 'video_auto_rescheduled_allowed')) {
                $table->boolean('video_auto_rescheduled_allowed')->default(0);
            }
            if (!Schema::hasColumn('doctors', 'auto_rescheduled_allowed_before_minutes')) {
                $table->unsignedInteger('auto_rescheduled_allowed_before_minutes')->default(1440);
            }
            if (!Schema::hasColumn('doctors', 'video_auto_rescheduled_allowed_before_minutes')) {
                $table->unsignedInteger('video_auto_rescheduled_allowed_before_minutes')->default(1440);
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $columns = [
                'auto_rescheduled_allowed',
                'video_auto_rescheduled_allowed',
                'auto_rescheduled_allowed_before_minutes',
                'video_auto_rescheduled_allowed_before_minutes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('doctors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
