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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('offered_by')->nullable()->after('description');
            $table->unsignedInteger('number_of_lectures')->default(0)->after('offered_by');
            $table->decimal('total_duration', 5, 2)->default(0)->after('number_of_lectures');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['offered_by', 'number_of_lectures', 'total_duration']);
        });
    }
};