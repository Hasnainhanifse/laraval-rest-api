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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('order_no');
            $table->enum('status', ['W', 'P', 'A', 'R'])->default('W');
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
            
            // Make order_no unique only within supplier_id scope
            $table->unique(['supplier_id', 'order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
