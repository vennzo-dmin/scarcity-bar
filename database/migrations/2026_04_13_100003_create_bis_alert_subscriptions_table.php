<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_alert_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscriber_id');
            $table->string('type', 20);                 // back_in_stock | price_drop
            $table->string('channel', 20)->default('email'); // email | sms | both
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_handle')->nullable();
            $table->string('product_title');
            $table->string('variant_title')->nullable();
            $table->string('image_url', 512)->nullable();
            $table->decimal('price_at_subscription', 12, 2)->nullable();
            $table->string('currency', 6)->nullable();
            $table->string('status', 20)->default('active'); // active | queued | sent | cancelled | failed
            $table->timestamp('notified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'variant_id', 'status'], 'bis_alert_variant_idx');
            $table->index(['user_id', 'type', 'product_id', 'status'], 'bis_alert_product_idx');
            $table->index(['user_id', 'subscriber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_alert_subscriptions');
    }
};
