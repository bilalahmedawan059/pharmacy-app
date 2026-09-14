<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSaleTransactionIdToSalesTable extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('sale_transaction_id')
                ->nullable()
                ->after('product_id')
                ->constrained('sale_transactions')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['sale_transaction_id']);
            $table->dropColumn('sale_transaction_id');
        });
    }
}