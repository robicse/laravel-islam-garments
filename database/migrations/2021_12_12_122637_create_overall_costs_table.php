<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOverallCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overall_costs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('overall_cost_category_id');
            $table->enum('payment_type',['Cash','Cheque']);
            $table->string('cheque_number')->nullable();
            $table->float('amount', 8,2);
            $table->string('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('overall_costs');
    }
}
