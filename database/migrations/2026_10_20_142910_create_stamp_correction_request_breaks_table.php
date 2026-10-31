<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stamp_correction_request_breaks', function (Blueprint $table) {
            $table->id();

            // どの修正申請に紐づく休憩時間なのかを記録する
            $table->foreignId('stamp_correction_request_id');

            $table->foreign('stamp_correction_request_id', 'scrb_request_id_fk')
                ->references('id')
                ->on('stamp_correction_requests')
                ->onDelete('cascade');

            // 申請する休憩開始・終了時間
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_request_breaks');
    }
};
