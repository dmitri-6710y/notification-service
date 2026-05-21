<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('recipient_id');
            $table->enum('channel', ['sms', 'email']);
            $table->text('message');
            $table->enum('priority', ['high', 'low'])->default('low');
            $table->enum('status', [
                'pending',
                'queued',
                'sent',
                'delivered',
                'failed'
            ])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->string('idempotency_key')->nullable()->index();
            $table->timestamps();

            $table->index('recipient_id');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
