<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnboardingFieldsToPharmaciesTable extends Migration
{
    public function up()
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('id');
            $table->string('owner_name')->nullable()->after('business_name');
            $table->string('owner_email')->nullable()->after('owner_name');
            $table->string('tax_type', 10)->nullable()->after('owner_email');
            $table->string('tax_id', 30)->nullable()->after('tax_type');
            $table->text('contact_address')->nullable()->after('tax_id');
            $table->string('city')->nullable()->after('contact_address');
            $table->string('status', 20)->default('pending')->after('city');
            $table->timestamp('launched_at')->nullable()->after('status');
            $table->unique(['tax_type', 'tax_id']);
        });
    }

    public function down()
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropUnique('pharmacies_tax_type_tax_id_unique');
            $table->dropColumn([
                'business_name', 'owner_name', 'owner_email', 'tax_type', 'tax_id',
                'contact_address', 'city', 'status', 'launched_at',
            ]);
        });
    }
}