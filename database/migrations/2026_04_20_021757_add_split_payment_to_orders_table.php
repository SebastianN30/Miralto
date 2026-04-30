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
        Schema::table('orders', function (Blueprint $table) {
            // Amount paid with the first payment method (null = full total)
            $table->decimal('payment_amount_1', 10, 2)->nullable()->after('payment_method');
            // Second payment method and its amount
            $table->enum('payment_method_2', ['cash', 'transfer', 'card'])->nullable()->after('payment_amount_1');
            $table->decimal('payment_amount_2', 10, 2)->nullable()->after('payment_method_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_amount_1', 'payment_method_2', 'payment_amount_2']);
        });
    }
};
