// database/migrations/2025_10_10_000000_drop_legacy_address_columns_from_purchases.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) 念のため delivery_* に旧値を補完（NULLのところだけ上書き）
        if (Schema::hasColumn('purchases', 'postal_code') && Schema::hasColumn('purchases', 'delivery_postal_code')) {
            DB::statement("UPDATE purchases SET delivery_postal_code = COALESCE(delivery_postal_code, postal_code)");
        }
        if (Schema::hasColumn('purchases', 'address') && Schema::hasColumn('purchases', 'delivery_address')) {
            DB::statement("UPDATE purchases SET delivery_address = COALESCE(delivery_address, address)");
        }
        if (Schema::hasColumn('purchases', 'building_name') && Schema::hasColumn('purchases', 'delivery_building_name')) {
            DB::statement("UPDATE purchases SET delivery_building_name = COALESCE(delivery_building_name, building_name)");
        }

        // 2) 存在する列だけ削除
        $dropPostal   = Schema::hasColumn('purchases', 'postal_code');
        $dropAddress  = Schema::hasColumn('purchases', 'address');
        $dropBuilding = Schema::hasColumn('purchases', 'building_name');

        if ($dropPostal || $dropAddress || $dropBuilding) {
            Schema::table('purchases', function (Blueprint $table) use ($dropPostal, $dropAddress, $dropBuilding) {
                $cols = [];
                if ($dropPostal)   $cols[] = 'postal_code';
                if ($dropAddress)  $cols[] = 'address';
                if ($dropBuilding) $cols[] = 'building_name';
                $table->dropColumn($cols);
            });
        }
    }

    public function down(): void
    {
        // 1) 旧カラムを復元
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'postal_code')) {
                $table->string('postal_code', 10)->nullable()->after('total');
            }
            if (!Schema::hasColumn('purchases', 'address')) {
                $table->string('address', 255)->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('purchases', 'building_name')) {
                $table->string('building_name', 255)->nullable()->after('address');
            }
        });

        // 2) delivery_* から旧カラムへ戻し（空の所だけ）
        if (Schema::hasColumn('purchases', 'postal_code') && Schema::hasColumn('purchases', 'delivery_postal_code')) {
            DB::statement("UPDATE purchases SET postal_code = delivery_postal_code
                           WHERE (postal_code IS NULL OR postal_code = '') AND delivery_postal_code IS NOT NULL");
        }
        if (Schema::hasColumn('purchases', 'address') && Schema::hasColumn('purchases', 'delivery_address')) {
            DB::statement("UPDATE purchases SET address = delivery_address
                           WHERE (address IS NULL OR address = '') AND delivery_address IS NOT NULL");
        }
        if (Schema::hasColumn('purchases', 'building_name') && Schema::hasColumn('purchases', 'delivery_building_name')) {
            DB::statement("UPDATE purchases SET building_name = delivery_building_name
                           WHERE (building_name IS NULL OR building_name = '') AND delivery_building_name IS NOT NULL");
        }
    }
};
