<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('date');
            $table->enum('transaction_type',['Vehicle Vendor Rent','Vehicle Customer Rent','Driver Salary','Staff Salary']);
            $table->integer('order_id');
            $table->integer('paid_user_id')->nullable();
            $table->integer('payment_type_id');
            $table->double('paid',8,2)->default(0);
            $table->double('exchange',8,2)->default(0);
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
        Schema::dropIfExists('payments');
    }
}
