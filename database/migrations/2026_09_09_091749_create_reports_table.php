<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Người gửi báo cáo
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Tin bị báo cáo
            // Nếu Admin xóa tin, report vẫn được giữ lại
            $table->foreignId('property_id')
                ->nullable()
                ->constrained('properties')
                ->nullOnDelete();

            $table->string('reason');

            $table->text('description')
                ->nullable();

            // pending  = chờ xử lý
            // ignored  = bỏ qua
            // resolved = đã xử lý
            $table->string('status')
                ->default('pending');

            $table->timestamp('handled_at')
                ->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index([
                'user_id',
                'property_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
