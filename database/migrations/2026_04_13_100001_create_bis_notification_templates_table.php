<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_notification_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 20);         // back_in_stock | price_drop
            $table->string('channel', 10);      // email | sms
            $table->string('locale', 10)->default('en');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'type', 'channel', 'locale'], 'bis_tpl_unique');
            $table->index(['user_id', 'type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_notification_templates');
    }
};
