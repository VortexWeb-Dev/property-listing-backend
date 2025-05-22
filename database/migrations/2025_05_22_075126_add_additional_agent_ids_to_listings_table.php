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
            $table->foreignId('pf_agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('website_agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('bayut_dubizzle_agent_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['pf_agent_id']);
            $table->dropColumn('pf_agent_id');

            $table->dropForeign(['website_agent_id']);
            $table->dropColumn('website_agent_id');

            $table->dropForeign(['bayut_dubizzle_agent_id']);
            $table->dropColumn('bayut_dubizzle_agent_id');
        });
    }
};