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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->string('payment_gateway')->default('cashfree');
            $table->string('payment_id')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('plan_details')->nullable(); // Store a snapshot of the plan limits at the time of purchase
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
