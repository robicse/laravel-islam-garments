<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('supplier_code');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('supplier_address')->nullable();
            $table->float('total_purchase_amount',8,2)->default(0);
            $table->float('total_receive_amount',8,2)->default(0);
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
        Schema::dropIfExists('suppliers');
    }
}
