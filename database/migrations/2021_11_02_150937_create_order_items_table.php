<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('date');
            $table->integer('order_id');
            $table->string('type');
            $table->integer('vehicle_id');
            $table->integer('driver_id');
            $table->string('rent_type');
            $table->string('start_date');
            $table->string('end_date');
            $table->integer('rent_duration')->nullable();
            $table->integer('quantity')->default(1);
            $table->double('price',8,2)->default(0);
            $table->double('discount',8,2)->default(0);
            $table->double('per_day_rent',8,2)->default(0);
            $table->double('sub_total',8,2)->default(0);
            $table->string('note',8,2)->nullable();
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
        Schema::dropIfExists('order_items');
    }
}
