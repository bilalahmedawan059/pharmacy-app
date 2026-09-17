<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPharmacyScopeToRoles extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('pharmacy_id')->nullable()->after('id')->constrained('pharmacies')->nullOnDelete();
            $table->index('pharmacy_id');
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
            $table->dropIndex(['pharmacy_id']);
            $table->dropColumn('pharmacy_id');
        });
    }
}