<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_subscribers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('locale', 10)->nullable();
            $table->unsignedBigInteger('shopify_customer_id')->nullable();
            $table->boolean('consent_email')->default(true);
            $table->boolean('consent_sms')->default(false);
            $table->timestamp('email_unsubscribed_at')->nullable();
            $table->timestamp('sms_unsubscribed_at')->nullable();
            $table->string('unsubscribe_token', 64)->unique();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'email']);
            $table->index(['user_id', 'phone']);
            $table->index(['user_id', 'shopify_customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_subscribers');
    }
};
