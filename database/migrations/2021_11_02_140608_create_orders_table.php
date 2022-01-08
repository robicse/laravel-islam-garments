<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('date');
            $table->string('order_type');
            $table->integer('vendor_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->string('type');
            $table->string('invoice_no');
            $table->integer('payment_type_id');
            $table->double('sub_total',8,2)->default(0);
            $table->double('grand_discount',8,2)->default(0);
            $table->double('grand_total',8,2);
            $table->double('paid',8,2)->default(0);
            $table->double('due_price',8,2)->default(0);
            $table->double('exchange',8,2)->default(0);
            $table->double('profit',8,2)->default(0);
            $table->string('status');
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
        Schema::dropIfExists('orders');
    }
}
