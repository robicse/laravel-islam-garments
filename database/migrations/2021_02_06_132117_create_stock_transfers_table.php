<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_no');
            $table->bigInteger('user_id');
            $table->bigInteger('transfer_from_warehouse_id')->nullable();
            $table->bigInteger('transfer_to_store_id')->unsigned();
            $table->bigInteger('purchase_type_id')->nullable();
            $table->string('cheque_date')->nullable();
            $table->string('cheque_approved_status')->nullable();
            $table->float('sub_total_amount', 8,2);
            $table->text('miscellaneous_comment')->nullable();
            $table->float('miscellaneous_charge', 8,2);
            $table->float('total_vat_amount', 8,2);
            $table->float('less_amount', 8,2)->default(0);
            $table->float('grand_total_amount', 8,2);
            $table->float('paid_amount', 8,2);
            $table->float('due_amount', 8,2);
            $table->string('issue_date');
            $table->string('due_date')->nullable();
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
        Schema::dropIfExists('stock_transfers');
    }
}
