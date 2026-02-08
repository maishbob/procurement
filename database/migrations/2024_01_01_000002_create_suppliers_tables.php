<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suppliers (Kenya compliance aware)
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code', 20)->unique();
            $table->string('name');
            $table->string('trading_name')->nullable();
            $table->enum('type', ['individual', 'company', 'partnership', 'ngo', 'government']);

            // Kenya KRA Information
            $table->string('kra_pin', 11)->unique();
            $table->date('kra_pin_verified_at')->nullable();
            $table->date('tax_compliance_cert_expiry')->nullable();
            $table->string('tax_compliance_cert_number')->nullable();

            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('website')->nullable();

            // Physical Address
            $table->text('physical_address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Kenya');

            // Postal Address
            $table->string('postal_address')->nullable();
            $table->string('postal_city')->nullable();
            $table->string('postal_postal_code')->nullable();

            // Banking Information
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('swift_code')->nullable();

            // Contact Person
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_title')->nullable();
            $table->string('contact_person_phone', 20)->nullable();
            $table->string('contact_person_email')->nullable();

            // Tax Configuration
            $table->boolean('is_vat_registered')->default(false);
            $table->string('vat_number')->nullable();
            $table->string('default_wht_type')->nullable(); // services, professional_fees, etc.
            $table->decimal('custom_wht_rate', 5, 2)->nullable();

            // Supplier Classification
            $table->json('categories')->nullable(); // Array of product/service categories
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->integer('payment_terms_days')->default(30);
            $table->string('currency', 3)->default('KES');

            // Performance & Compliance
            $table->decimal('performance_rating', 3, 2)->nullable(); // 0.00 to 5.00
            $table->integer('total_orders')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            $table->integer('quality_issues')->default(0);

            // Blacklist Information
            $table->boolean('is_blacklisted')->default(false);
            $table->date('blacklisted_date')->nullable();
            $table->text('blacklist_reason')->nullable();
            $table->foreignId('blacklisted_by')->nullable()->constrained('users');

            // Status
            $table->enum('status', ['active', 'inactive', 'pending_approval', 'suspended', 'blacklisted'])->default('pending_approval');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // KRA PIN cert, compliance cert, etc.
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_blacklisted']);
            $table->index('kra_pin');
            $table->index('performance_rating');
        });

        // Supplier Categories
        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('supplier_categories');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Supplier Category Mapping
        Schema::create('supplier_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('supplier_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['supplier_id', 'category_id']);
        });

        // Supplier Contacts (multiple contacts per supplier)
        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['supplier_id', 'is_primary']);
        });

        // Supplier Documents
        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('document_type', 50); // kra_pin, tax_cert, company_reg, bank_statement
            $table->string('document_number')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['active', 'expired', 'superseded'])->default('active');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['supplier_id', 'document_type']);
            $table->index('expiry_date');
        });

        // Supplier Performance Reviews
        Schema::create('supplier_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('review_period', 50); // Q1 2024, 2024, etc.
            $table->date('review_date');
            $table->foreignId('reviewed_by')->constrained('users');

            // Rating criteria (1-5)
            $table->integer('quality_rating')->nullable();
            $table->integer('delivery_rating')->nullable();
            $table->integer('pricing_rating')->nullable();
            $table->integer('service_rating')->nullable();
            $table->integer('compliance_rating')->nullable();
            $table->decimal('overall_rating', 3, 2)->nullable();

            // Metrics
            $table->integer('total_orders')->default(0);
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            $table->integer('rejected_deliveries')->default(0);
            $table->decimal('total_order_value', 15, 2)->default(0);

            // Comments
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();

            $table->index(['supplier_id', 'review_date']);
        });

        // Supplier Blacklist History
        Schema::create('supplier_blacklist_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->enum('action', ['blacklisted', 'removed']);
            $table->date('effective_date');
            $table->text('reason');
            $table->foreignId('actioned_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_blacklist_history');
        Schema::dropIfExists('supplier_performance_reviews');
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('supplier_contacts');
        Schema::dropIfExists('supplier_category_mappings');
        Schema::dropIfExists('supplier_categories');
        Schema::dropIfExists('suppliers');
    }
};
