<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supplier Documents
        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->enum('document_type', [
                'kra_pin_certificate',
                'tax_compliance_certificate',
                'bank_letter',
                'business_registration',
                'etims_registration',
                'other',
            ]);
            $table->string('file_path');
            $table->string('file_name');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Supplier Contacts
        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Supplier Performance Reviews
        Schema::create('supplier_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users');
            $table->string('review_period'); // e.g. "2025-Q4"
            $table->decimal('delivery_score', 4, 2)->nullable();   // 0-100
            $table->decimal('quality_score', 4, 2)->nullable();
            $table->decimal('compliance_score', 4, 2)->nullable();
            $table->decimal('overall_score', 4, 2)->nullable();
            $table->text('comments')->nullable();
            $table->boolean('action_required')->default(false);
            $table->text('action_details')->nullable();
            $table->timestamps();
        });

        // Supplier Categories (categories a supplier is approved to supply)
        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('category_name');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Supplier Blacklist History
        Schema::create('supplier_blacklist_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->enum('action', ['blacklisted', 'unblacklisted']);
            $table->text('reason');
            $table->unsignedBigInteger('actioned_by');
            $table->timestamp('actioned_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_blacklist_history');
        Schema::dropIfExists('supplier_categories');
        Schema::dropIfExists('supplier_performance_reviews');
        Schema::dropIfExists('supplier_contacts');
        Schema::dropIfExists('supplier_documents');
    }
};
