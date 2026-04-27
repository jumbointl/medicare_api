<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('appointment_reschedule_req')) {
            Schema::create('appointment_reschedule_req', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id');
                $table->string('status', 32)->default('Initiated');
                $table->date('requested_date')->nullable();
                $table->string('requested_time_slots', 255)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index('appointment_id');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reschedule_req');
    }
};
