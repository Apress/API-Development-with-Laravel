<?php

/*database/migrations/xxxx_xx_xx_xxxxxx_create_transactions_table.php*/

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('transaction_ref')->unique();
            $table->string('email', 150);
            $table->string('currency', 5);
            $table->string('redirect_url', 500);
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->string('status', 15)->default('pending');
            $table->boolean('pass_charge');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
