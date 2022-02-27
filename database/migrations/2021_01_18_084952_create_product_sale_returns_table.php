<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSaleReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_sale_returns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_no');
            $table->string('product_sale_invoice_no');
            $table->bigInteger('user_id');
            $table->bigInteger('supplier_id')->unsigned();
            $table->bigInteger('store_id')->unsigned();
            $table->float('sub_total_amount', 16,2);
            $table->string('product_sale_return_type');
            $table->enum('discount_type', ['Flat','Percent'])->nullable();
            $table->string('discount_amount')->nullable();
            $table->float('grand_total_amount', 8,2);
            $table->float('paid_amount', 16,2);
            $table->float('due_amount', 16,2);
            $table->string('product_sale_return_date');
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
        Schema::dropIfExists('product_sale_returns');
    }
}
