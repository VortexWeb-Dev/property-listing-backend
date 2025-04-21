<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('listings', function (Blueprint $table) {
            if (!Schema::hasColumn('listings', 'pf_enable')) {
                $table->boolean('pf_enable')->default(false);
            }
            if (!Schema::hasColumn('listings', 'bayut_enable')) {
                $table->boolean('bayut_enable')->default(false);
            }
            if (!Schema::hasColumn('listings', 'dubizzle_enable')) {
                $table->boolean('dubizzle_enable')->default(false);
            }
            if (!Schema::hasColumn('listings', 'website_enable')) {
                $table->boolean('website_enable')->default(false);
            }

            // Don't add 'status' again here, it's already present
        });
    }

    public function down(): void {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'pf_enable',
                'bayut_enable',
                'dubizzle_enable',
                'website_enable'
                // Do not drop 'status' if you plan to keep it
            ]);
        });
    }
};