<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSaleReturnDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_sale_return_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('product_sale_return_id');
            $table->bigInteger('product_sale_detail_id');
            $table->bigInteger('product_id')->unsigned();
            $table->string('purchase_price')->default(0);
            $table->integer('qty');
            $table->float('price', 8,2);
            $table->float('sub_total', 8,2);
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
        Schema::dropIfExists('product_sale_return_details');
    }
}
