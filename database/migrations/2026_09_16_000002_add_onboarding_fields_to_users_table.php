<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnboardingFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('pharmacy_id')->nullable()->after('id')->constrained('pharmacies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('pharmacy_id')->constrained('branches')->nullOnDelete();
            $table->string('cnic', 25)->nullable()->after('email');
            $table->string('phone', 30)->nullable()->after('cnic');
            $table->boolean('is_invited')->default(false)->after('phone');
            $table->timestamp('invited_at')->nullable()->after('is_invited');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['pharmacy_id']);
            $table->dropColumn(['pharmacy_id', 'branch_id', 'cnic', 'phone', 'is_invited', 'invited_at']);
        });
    }
}