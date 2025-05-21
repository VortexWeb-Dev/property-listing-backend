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
        // Step 1: Rename old column to preserve data (optional safety)
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('project_status', 'old_project_status');
        });

        // Step 2: Add new project_status column with updated enum values + new fields
        Schema::table('listings', function (Blueprint $table) {
            $table->enum('project_status', [
                'Off Plan',
                'Off-Plan Primary',
                'Off-Plan Secondary',
                'Ready Primary',
                'Ready Secondary',
                'Completed'
            ])->nullable();

            $table->string('landlord_email')->nullable()->after('landlord_contact');
            $table->string('comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['project_status', 'landlord_email', 'comments']);
            $table->renameColumn('old_project_status', 'project_status');
        });
    }
};