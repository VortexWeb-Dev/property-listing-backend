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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone');
            $table->string('property_reference');
            $table->string('property_link');
            $table->string('tracking_link');
            $table->text('comment')->nullable();
            $table->foreignId('responsible_person')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->enum('source', [
                'PF Email', 'PF WhatsApp', 'PF Call',
                'Bayut Email', 'Bayut WhatsApp', 'Bayut Call',
                'Dubizzle Email', 'Dubizzle WhatsApp', 'Dubizzle Call',
                'Website Form'
            ]);
            $table->enum('stage', ['New', 'In Progress', 'Contacted', 'Success', 'Fail']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};