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
        Schema::create('payment_gateway_roles', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_provider')->default('pesapal'); // pesapal, mpesa, stripe, etc.
            $table->foreignId('user_id')->constrained('users');
            $table->enum('role_type', [
                'initiator',
                'approver',
                'processor',
                'reconciler',
                'admin'
            ]);
            $table->json('permissions')->nullable(); // Specific permissions like 'bulk_payment', 'refund', etc.
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['gateway_provider', 'user_id', 'role_type'], 'uk_gateway_user_role');
            $table->index('gateway_provider');
            $table->index('role_type');
            $table->index('is_active');
        });

        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_provider')->default('pesapal');
            $table->foreignId('payment_id')->constrained('payments');
            $table->string('gateway_transaction_id')->nullable(); // External transaction ID
            $table->string('merchant_reference')->nullable();
            $table->enum('transaction_type', [
                'payment',
                'refund',
                'reversal',
                'enquiry'
            ])->default('payment');
            $table->enum('transaction_status', [
                'initiated',
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'reversed'
            ])->default('initiated');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('KES');

            // Segregation of duties tracking
            $table->foreignId('initiated_by')->nullable()->constrained('users');
            $table->timestamp('initiated_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users');
            $table->timestamp('reconciled_at')->nullable();

            // Response data
            $table->json('gateway_request')->nullable();
            $table->json('gateway_response')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            $table->timestamps();

            $table->index('gateway_transaction_id');
            $table->index('merchant_reference');
            $table->index('transaction_status');
        });

        Schema::create('payment_gateway_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_provider')->default('pesapal');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('payment_gateway_transaction_id')->nullable()->constrained('payment_gateway_transactions');
            $table->string('action'); // e.g., 'initiate_payment', 'approve_payment', 'process_payment', 'reconcile'
            $table->enum('action_result', ['success', 'failure', 'blocked'])->default('success');
            $table->text('action_details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('gateway_provider');
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop payment_gateway_transactions before payments (FK)
        if (Schema::hasTable('payment_gateway_audit_log')) {
            Schema::dropIfExists('payment_gateway_audit_log');
        }
        if (Schema::hasTable('payment_gateway_transactions')) {
            Schema::dropIfExists('payment_gateway_transactions');
        }
        if (Schema::hasTable('payment_gateway_roles')) {
            Schema::dropIfExists('payment_gateway_roles');
        }
    }
};
