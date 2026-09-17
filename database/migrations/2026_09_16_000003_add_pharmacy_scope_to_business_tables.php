<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPharmacyScopeToBusinessTables extends Migration
{
    private $tables = ['categories', 'suppliers', 'purchases', 'products', 'sales', 'sale_transactions'];

    public function up()
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('pharmacy_id')->nullable()->after('id')->constrained('pharmacies')->nullOnDelete();
                $table->index('pharmacy_id');
            });
        }
    }

    public function down()
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['pharmacy_id']);
                $table->dropIndex([$table->getTable() . '_pharmacy_id_index']);
                $table->dropColumn('pharmacy_id');
            });
        }
    }
}