<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('type', 32)->default('cash');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['ledger_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
