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
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 100);
            $table->string('customer_name', 150)->nullable();
            $table->string('service_name', 150)->nullable();
            $table->foreignId('inputer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('account_manager_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', [
                'provisioning',
                'pending_baso',
                'pending_billing_approval',
                'complete',
                'failed',
                'cancel_abandoned',
            ])->index();
            $table->string('provisioning_stage', 150)->nullable();
            $table->char('period_month', 7)->index();
            $table->string('source_system', 100)->default('Dashboard NCX');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_number', 'period_month']);
            $table->index(['inputer_id', 'period_month']);
            $table->index(['account_manager_id', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
