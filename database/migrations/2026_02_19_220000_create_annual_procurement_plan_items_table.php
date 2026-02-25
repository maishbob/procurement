// This is the active migration for annual_procurement_plan_items table.
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('annual_procurement_plan_items');
        Schema::enableForeignKeyConstraints();

        Schema::create('annual_procurement_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annual_procurement_plan_id');
            $table->string('category');
            $table->string('description');
            $table->string('planned_quarter');
            $table->decimal('estimated_value', 18, 2)->nullable(); // legacy, keep for compatibility
            $table->decimal('estimated_quantity', 18, 2)->nullable();
            $table->decimal('estimated_unit_price', 18, 2)->nullable();
            $table->decimal('estimated_total', 18, 2)->nullable(); // used by model/service
            $table->decimal('budgeted_amount', 18, 2)->nullable(); // used by model/service
            $table->string('sourcing_method')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('annual_procurement_plan_id')
                ->references('id')->on('annual_procurement_plans')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_procurement_plan_items');
    }
};
