<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_no');
            $table->bigInteger('user_id');
            $table->bigInteger('customer_id');
            $table->bigInteger('warehouse_id')->nullable();
            $table->bigInteger('store_id')->nullable();
            $table->bigInteger('purchase_type_id')->nullable();
            $table->string('cheque_date')->nullable();
            $table->string('cheque_approved_status')->nullable();
            $table->enum('sale_type', ['whole_sale','pos_sale']);
            $table->float('sub_total_amount', 8,2);
            $table->enum('discount_type', ['Flat','Percentage'])->nullable();
            $table->string('discount_amount')->nullable();
            $table->text('miscellaneous_comment')->nullable();
            $table->float('miscellaneous_charge', 8,2);
            $table->float('paid_amount', 8,2);
            $table->float('due_amount', 8,2);
            $table->float('total_vat_amount', 8,2);
            $table->float('less_amount', 8,2)->default(0);
            $table->float('grand_total_amount', 8,2);
            $table->float('grand_profit_amount', 8,2)->default(0);
            $table->string('sale_date');
            $table->string('sale_date_time');
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
        Schema::dropIfExists('product_sales');
    }
}
