<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('price');
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->unsignedInteger('total');
            $table->string('postal_code', 10);
            $table->string('address', 255);
            $table->string('building_name', 255)->nullable();
            $table->string('payment_method', 50)->default('convenience_store');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchases');
    }
};
