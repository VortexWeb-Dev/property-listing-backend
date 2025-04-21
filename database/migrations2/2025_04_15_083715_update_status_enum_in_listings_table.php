<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Rename old column
        Schema::table('listings', function ($table) {
            $table->renameColumn('status', 'status_old');
        });

        // Add new enum-like column
        Schema::table('listings', function ($table) {
            $table->string('status')->default('unpublished');
        });

        // Copy old values (if needed, map '0', '1', '2' to the new values)
        DB::table('listings')->update([
            'status' => DB::raw("
                CASE status_old
                    WHEN '0' THEN 'draft'
                    WHEN '1' THEN 'live'
                    WHEN '2' THEN 'archived'
                    ELSE 'unpublished'
                END
            ")
        ]);

        // Drop old column
        Schema::table('listings', function ($table) {
            $table->dropColumn('status_old');
        });
    }

    public function down(): void {
        Schema::table('listings', function ($table) {
            $table->dropColumn('status');
            $table->enum('status', ['0', '1', '2'])->default('1');
        });
    }
};