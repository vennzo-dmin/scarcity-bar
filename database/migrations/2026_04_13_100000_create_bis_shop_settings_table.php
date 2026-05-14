<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_shop_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('onboarded')->default(false);
            $table->string('default_locale', 10)->default('en');
            $table->json('widget')->nullable();
            $table->json('inventory')->nullable();
            $table->json('pricing')->nullable();
            $table->json('consent')->nullable();
            $table->json('sending')->nullable();
            $table->json('locales')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_shop_settings');
    }
};
