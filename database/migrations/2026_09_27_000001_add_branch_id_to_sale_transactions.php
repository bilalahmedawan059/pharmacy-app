<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToSaleTransactions extends Migration
{
    public function up()
    {
        Schema::table('sale_transactions', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('pharmacy_id')->constrained('branches')->nullOnDelete();
            $table->index('branch_id');
        });

        DB::table('sale_transactions')
            ->join('users', 'sale_transactions.user_id', '=', 'users.id')
            ->whereNotNull('users.branch_id')
            ->select('sale_transactions.id', 'users.branch_id')
            ->orderBy('sale_transactions.id')
            ->chunk(500, function ($transactions) {
                foreach ($transactions as $transaction) {
                    DB::table('sale_transactions')
                        ->where('id', $transaction->id)
                        ->update(['branch_id' => $transaction->branch_id]);
                }
            });
    }

    public function down()
    {
        Schema::table('sale_transactions', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
}