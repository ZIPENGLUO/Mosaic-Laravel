<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['income', 'expense']);
            // Validate amount > 0 and matching ledger/category type in the application.
            $table->decimal('amount', 15, 2);
            $table->dateTime('occurred_at');
            $table->string('merchant')->nullable();
            $table->text('note')->nullable();
            $table->enum('source', ['manual', 'ocr'])->default('manual');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['ledger_id', 'occurred_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
