<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // 既存に合わせて「無ければ追加」スタイル
            if (!Schema::hasColumn('purchases', 'quantity')) {
                $table->unsignedSmallInteger('quantity')->default(1)->after('item_id');
            }
            if (!Schema::hasColumn('purchases', 'price')) {
                $table->unsignedInteger('price')->after('quantity');
            }
            if (!Schema::hasColumn('purchases', 'shipping_fee')) {
                $table->unsignedInteger('shipping_fee')->default(0)->after('price');
            }
            if (!Schema::hasColumn('purchases', 'total')) {
                $table->unsignedInteger('total')->after('shipping_fee');
            }
            if (!Schema::hasColumn('purchases', 'postal_code')) {
                $table->string('postal_code', 10)->nullable()->after('total');
            }
            if (!Schema::hasColumn('purchases', 'address')) {
                $table->string('address')->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('purchases', 'building_name')) {
                $table->string('building_name')->nullable()->after('address');
            }
            if (!Schema::hasColumn('purchases', 'payment_method')) {
                $table->string('payment_method', 32)->nullable()->after('building_name');
            }
            if (!Schema::hasColumn('purchases', 'payment_intent_id')) {
                $table->string('payment_intent_id', 64)->nullable()->after('payment_method');
            }

            // 1商品=1回だけ売れる仕様なら、ユニーク制約を推奨
            // 既存データに重複が無い前提。心配ならコメントアウトしてOK
            // if (!Schema::hasColumn('purchases', 'item_id')) { /* 既にある想定 */ }
            // $table->unique('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // 元に戻す（必要なら）
            if (Schema::hasColumn('purchases', 'payment_intent_id')) $table->dropColumn('payment_intent_id');
            if (Schema::hasColumn('purchases', 'payment_method'))    $table->dropColumn('payment_method');
            if (Schema::hasColumn('purchases', 'building_name'))     $table->dropColumn('building_name');
            if (Schema::hasColumn('purchases', 'address'))           $table->dropColumn('address');
            if (Schema::hasColumn('purchases', 'postal_code'))       $table->dropColumn('postal_code');
            if (Schema::hasColumn('purchases', 'total'))             $table->dropColumn('total');
            if (Schema::hasColumn('purchases', 'shipping_fee'))      $table->dropColumn('shipping_fee');
            if (Schema::hasColumn('purchases', 'price'))             $table->dropColumn('price');
            if (Schema::hasColumn('purchases', 'quantity'))          $table->dropColumn('quantity');
            // ユニーク制約を付けた場合は dropUnique も
            // $table->dropUnique(['item_id']);
        });
    }
};
