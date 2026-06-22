<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 40)->unique();
            $table->unsignedBigInteger('order_id');
            $table->string('city');
            $table->string('state');
            $table->string('country');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')
            ->references('id')
            ->on('orders')
            ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
