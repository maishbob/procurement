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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('capa_action_updates');
        Schema::dropIfExists('capa_actions');
        Schema::enableForeignKeyConstraints();

        Schema::create('capa_actions', function (Blueprint $table) {
            $table->id();
            $table->string('capa_number')->unique();
            $table->enum('type', ['corrective', 'preventive']); // CA or PA
            $table->string('title');
            $table->text('description');

            // Source/Trigger
            $table->enum('source', [
                'audit_finding',
                'variance_analysis',
                'complaint',
                'non_conformance',
                'process_improvement',
                'management_review',
                'risk_assessment',
                'other'
            ]);
            $table->string('source_reference')->nullable(); // e.g., audit ID, invoice ID
            $table->nullableMorphs('source_entity'); // Polymorphic relation to triggering entity

            // Problem Analysis
            $table->text('problem_statement');
            $table->text('root_cause_analysis')->nullable();
            $table->text('immediate_action_taken')->nullable();

            // Corrective/Preventive Action Details
            $table->text('proposed_action');
            $table->text('implementation_plan')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();

            // Assignment
            $table->foreignId('raised_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');

            // Status & Workflow
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'in_progress',
                'pending_verification',
                'verified',
                'closed',
                'rejected',
                'cancelled'
            ])->default('draft');

            // Approval tracking
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_comments')->nullable();

            // Verification tracking
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_comments')->nullable();
            $table->boolean('verification_passed')->nullable();

            // Effectiveness Review
            $table->date('effectiveness_review_date')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('effectiveness_review_comments')->nullable();
            $table->enum('effectiveness_rating', [
                'effective',
                'partially_effective',
                'ineffective',
                'pending'
            ])->nullable();

            // Cost tracking
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();

            // Attachments & Documentation
            $table->json('attachments')->nullable();
            $table->text('lessons_learned')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
            $table->index('source');
            $table->index('priority');
            $table->index('target_completion_date');
        });

        Schema::create('capa_action_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capa_action_id')->constrained('capa_actions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->text('update_description');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capa_action_updates');
        Schema::dropIfExists('capa_actions');
    }
};
