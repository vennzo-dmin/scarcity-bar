<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bis_shop_settings', function (Blueprint $table) {
            $table->text('providers')->nullable()->after('sending');
        });
    }

    public function down(): void
    {
        Schema::table('bis_shop_settings', function (Blueprint $table) {
            $table->dropColumn('providers');
        });
    }
};
