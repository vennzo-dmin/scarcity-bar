<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a dedicated `inventory_item_id` column so the
 * `inventory_levels/update` webhook can resolve a variant from the
 * Shopify payload (which only carries inventory_item_id, not variant_id).
 *
 * Previously the code queried whereJsonContains('metadata', ...) against a
 * column that never existed, which crashed every inventory webhook.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bis_product_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('variant_id');
            $table->index(['user_id', 'inventory_item_id'], 'bis_snap_user_invitem_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bis_product_snapshots', function (Blueprint $table) {
            $table->dropIndex('bis_snap_user_invitem_idx');
            $table->dropColumn('inventory_item_id');
        });
    }
};
