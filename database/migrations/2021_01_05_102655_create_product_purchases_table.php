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
            $table->string('supplier_invoice_no')->nullable();
            $table->bigInteger('user_id');
            $table->bigInteger('supplier_id');
            $table->bigInteger('warehouse_id');
            $table->bigInteger('purchase_type_id');
            $table->string('cheque_date')->nullable();
            $table->string('cheque_approved_status')->nullable();
            $table->float('sub_total_amount', 8,2);
            $table->enum('discount_type', ['Flat','Percentage'])->nullable();
            $table->string('discount_amount')->nullable();
            $table->string('after_discount_amount')->nullable();
            $table->float('less_amount', 8,2)->default(0);
            $table->float('grand_total_amount', 8,2);
            $table->float('paid_amount', 8,2);
            $table->float('due_amount', 8,2);
            $table->string('purchase_date');
            $table->string('purchase_date_time');
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
        Schema::dropIfExists('product_purchases');
    }
}
