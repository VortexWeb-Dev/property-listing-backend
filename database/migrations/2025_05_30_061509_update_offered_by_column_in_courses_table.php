<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop existing string column
            $table->dropColumn('offered_by');
        });

        Schema::table('courses', function (Blueprint $table) {
            // Add foreign key column
            $table->foreignId('offered_by')
                  ->nullable()
                  ->after('description')
                  ->constrained('companies')
                  ->nullOnDelete(); // or ->cascadeOnDelete()
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop the foreign key constraint and column
            $table->dropForeign(['offered_by']);
            $table->dropColumn('offered_by');

            // Restore as string
            $table->string('offered_by')->nullable()->after('description');
        });
    }
};