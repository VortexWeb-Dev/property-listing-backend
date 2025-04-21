<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // First, drop the foreign key and the column
            $table->dropForeign(['landlord_id']);
            $table->dropColumn('landlord_id');

            // Then, add the new company_id foreign key
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Reverse: Drop the company_id
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            // Re-add landlord_id
            $table->foreignId('landlord_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};