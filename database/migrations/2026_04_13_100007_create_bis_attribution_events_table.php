<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_attribution_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('alert_subscription_id')->nullable();
            $table->unsignedBigInteger('delivery_log_id')->nullable();
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->string('event', 20); // click | order
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 6)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'event']);
            $table->index(['user_id', 'occurred_at']);
            $table->index('alert_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_attribution_events');
    }
};
