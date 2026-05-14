<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bis_product_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id');
            $table->integer('inventory_total')->default(0);
            $table->integer('inventory_sellable')->default(0);
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->string('currency', 6)->nullable();
            $table->boolean('available')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'variant_id']);
            $table->index(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bis_product_snapshots');
    }
};
