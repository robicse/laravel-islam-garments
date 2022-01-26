<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_no');
            $table->bigInteger('user_id');
            $table->bigInteger('party_id')->unsigned();
            $table->bigInteger('warehouse_id')->unsigned();
            $table->enum('purchase_type', ['whole_purchase','pos_purchase']);
            $table->enum('discount_type', ['Flat','Percentage'])->nullable();
            $table->string('discount_amount')->nullable();
            $table->float('paid_amount', 8,2);
            $table->float('due_amount', 8,2);
            $table->float('total_amount', 8,2);
            $table->string('purchase_date');
            $table->string('purchase_date_time');
            $table->timestamps();
            $table->foreign('party_id')->references('id')->on('parties')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_purchases');
    }
}
