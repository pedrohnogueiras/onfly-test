<?php

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_status', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->timestamps();
        });

        foreach (OrderStatusEnum::getDescriptionMap() as $id => $description) {
            DB::table('order_status')->insert([
                'id' => $id,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status');
    }
};
