<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomesTable extends Migration
{
    public function up()
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade'); // Foreign key reference to items table
            $table->text('note')->nullable(); // Add note column
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('incomes');
    }
}
