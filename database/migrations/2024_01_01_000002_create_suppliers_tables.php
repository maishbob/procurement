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

            // Supplier Categories, Contacts, Documents, and Performance Reviews table creation removed; handled in 2026_02_19_130000_create_supplier_sub_tables.php
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
            // $table->index('kra_pin'); // Removed: kra_pin column does not exist in this migration
            // $table->index('performance_rating'); // Removed: performance_rating column does not exist in this migration
        });

        // Supplier Categories table creation removed; handled in 2026_02_19_130000_create_supplier_sub_tables.php

        // Supplier Category Mapping table creation removed; handled in 2026_02_19_130000_create_supplier_sub_tables.php

        // Supplier Contacts table creation removed; handled in 2026_02_19_130000_create_supplier_sub_tables.php

        // Supplier Documents table creation removed; handled in 2026_02_19_130000_create_supplier_sub_tables.php

        // Supplier Performance Reviews table creation removed; handled in 2026_02_19_130000_create_supplier_sub_tables.php

        // Supplier Blacklist History table creation removed; handled in 2026_02_19_130000_create_supplier_sub_tables.php
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
