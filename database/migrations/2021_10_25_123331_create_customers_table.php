<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('customer_code');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('customer_address')->nullable();
            $table->float('total_sale_amount',8,2)->default(0);
            $table->float('total_paid_amount',8,2)->default(0);
            $table->float('total_due_amount',8,2)->default(0);
            $table->integer('total_transaction')->nullable();
            $table->integer('status')->default(1);
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
        Schema::dropIfExists('customers');
    }
}
