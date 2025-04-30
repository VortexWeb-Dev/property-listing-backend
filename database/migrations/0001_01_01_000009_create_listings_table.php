<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable();
            $table->string('title')->nullable();
            $table->string('title_deed')->nullable();
            $table->string('property_type')->nullable();
            $table->string('offering_type')->nullable();
            $table->decimal('size', 10, 2)->nullable();
            $table->string('unit_no')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('parking')->nullable();
            $table->enum('furnished', ['0', '1', '2'])->nullable(); // 0=unfurnished, 1=semi, 2=furnished
            $table->decimal('total_plot_size', 10, 2)->nullable();
            $table->string('plot_size')->nullable();
            $table->string('built_up_area')->nullable();
            $table->string('layout_type')->nullable();
            $table->string('project_name')->nullable();
            $table->enum('project_status', ['1','2','3','4','5'])->nullable();
            $table->enum('sale_type', ['0','1','2'])->nullable();
            $table->foreignId('developer_id')->nullable()->constrained('developers')->onDelete('set null');
            $table->string('build_year')->nullable();
            $table->string('customer')->nullable();
            $table->string('rera_permit_number')->nullable();
            $table->date('rera_issue_date')->nullable();
            $table->date('rera_expiration_date')->nullable();
            $table->date('contract_expiry_date')->nullable();
            $table->string('rental_period')->nullable();
            $table->integer('price')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('financial_status')->nullable();
            $table->string('sale_type_1')->nullable();
            $table->string('title_en')->nullable();
            $table->string('title_ar')->nullable();
            $table->text('desc_en')->nullable();
            $table->text('desc_ar')->nullable();
            $table->string('geopoints')->nullable();
            $table->string('listing_owner')->nullable();
            $table->string('landlord_name')->nullable();
            $table->string('landlord_contact')->nullable();
            $table->foreignId('pf_location')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('bayut_location')->nullable()->constrained('locations')->onDelete('set null');
            $table->enum('availability', ['available','under_offer','reserved','sold'])->nullable();
            $table->date('available_from')->nullable();
            $table->decimal('emirate_amount', 10, 2)->nullable();
            $table->string('payment_option')->nullable();
            $table->enum('no_of_cheques', ['1','2'])->nullable();
            $table->decimal('contract_charges', 10, 2)->nullable();
            $table->foreignId('financial_status_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('contract_expiry')->nullable();
            $table->string('floor_plan')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('brochure')->nullable();
            $table->string('video_url')->nullable();
            $table->string('360_view_url')->nullable();
            $table->string('photos_urls')->nullable();
            $table->string('dtcm_permit_number')->nullable();
            // Final status column (renamed and values mapped)
            $table->enum('status', ['draft', 'live', 'archived', 'published', 'unpublished', 'pocket'])->default('unpublished');

            // $table->enum('property_finder', ['0','1'])->default('0');
            // $table->enum('dubizzle', ['0','1'])->default('0');
            // $table->enum('website', ['0','1'])->default('0');
             $table->enum('watermark', ['0','1'])->default('0');

            // New platform flags
            $table->boolean('pf_enable')->default(false);
            $table->boolean('bayut_enable')->default(false);
            $table->boolean('dubizzle_enable')->default(false);
            $table->boolean('website_enable')->default(false);

            // Foreign keys
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};