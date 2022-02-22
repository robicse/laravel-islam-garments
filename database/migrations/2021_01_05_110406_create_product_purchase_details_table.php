<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPurchaseDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_purchase_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('product_purchase_id')->unsigned();
            $table->bigInteger('product_id')->unsigned();
            $table->string('product_name')->nullable();
            $table->string('barcode');
            $table->integer('qty');
            $table->float('price', 8,2);
            $table->float('mrp_price', 8,2);
            $table->float('sub_total', 8,2);
            $table->timestamps();
            $table->foreign('product_purchase_id')->references('id')->on('product_purchases')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_purchase_details');
    }
}
