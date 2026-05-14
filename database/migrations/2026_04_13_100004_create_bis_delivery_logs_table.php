<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('alert_subscription_id')->nullable();
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->string('type', 20);
            $table->string('channel', 10);
            $table->string('provider', 30)->nullable();
            $table->string('recipient')->nullable();
            $table->string('status', 20)->default('queued'); // queued | sent | failed | bounced | clicked
            $table->string('provider_message_id')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type', 'channel']);
            $table->index('alert_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_delivery_logs');
    }
};
