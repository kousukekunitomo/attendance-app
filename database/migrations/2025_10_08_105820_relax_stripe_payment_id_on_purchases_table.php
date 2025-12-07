<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('purchases', 'stripe_payment_id')) {
            DB::statement("ALTER TABLE purchases MODIFY stripe_payment_id VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        // もとに戻すなら（必要なら有効化）
        // DB::statement("ALTER TABLE purchases MODIFY stripe_payment_id VARCHAR(255) NOT NULL");
    }
};
