<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('type', 40); // low_stock | high_demand | internal_notify | export
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_automation_rules');
    }
};
