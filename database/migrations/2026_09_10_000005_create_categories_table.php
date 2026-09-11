<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['ledger_id', 'type', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
