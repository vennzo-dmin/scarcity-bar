<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('target', 30); // widget_label | popup_layout | email_subject | cta_copy | send_timing
            $table->string('status', 20)->default('draft'); // draft | running | paused | completed
            $table->json('variants'); // [{key, weight, config}]
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('bis_ab_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ab_test_id');
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->string('variant_key', 40);
            $table->string('event', 20)->default('view'); // view | subscribe | click | order
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['ab_test_id', 'variant_key']);
            $table->index('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_ab_assignments');
        Schema::dropIfExists('bis_ab_tests');
    }
};
