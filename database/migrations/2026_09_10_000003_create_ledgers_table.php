<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            // NULL means a personal ledger.
            $table->foreignId('family_id')->nullable()->constrained()->restrictOnDelete();
            $table->char('currency', 3)->default('CNY');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ledgers');
    }
};
