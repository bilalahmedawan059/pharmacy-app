<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('purchases', 'deleted_at')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('batches', 'deleted_at')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('batches', 'deleted_at')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
