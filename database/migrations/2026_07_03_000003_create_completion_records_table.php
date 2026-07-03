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
        Schema::create('completion_records', function (Blueprint $table) {
            $table->id();
            $table->string('completion_number', 100);
            $table->foreignId('order_status_id')->nullable()->constrained('order_statuses')->nullOnDelete();
            $table->foreignId('order_edk_id')->nullable()->constrained('order_edks')->nullOnDelete();
            $table->foreignId('inputer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('account_manager_id')->constrained('users')->restrictOnDelete();
            $table->enum('approval_status', [
                'menunggu_persetujuan',
                'disetujui',
                'tidak_disetujui',
                'revisi',
            ])->index();
            $table->date('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('revision_note')->nullable();
            $table->char('period_month', 7)->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['completion_number', 'period_month']);
            $table->index(['inputer_id', 'period_month']);
            $table->index(['account_manager_id', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completion_records');
    }
};
