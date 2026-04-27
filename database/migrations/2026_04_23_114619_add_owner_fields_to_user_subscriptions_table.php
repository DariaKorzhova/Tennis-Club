<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnerFieldsToUserSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('owner_type', ['user', 'child'])->default('user')->after('plan_id');
            $table->unsignedBigInteger('owner_id')->nullable()->after('owner_type');

            $table->index(['owner_type', 'owner_id'], 'user_subscriptions_owner_index');
        });

        DB::table('user_subscriptions')->update([
            'owner_type' => 'user',
            'owner_id' => DB::raw('user_id'),
        ]);
    }

    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex('user_subscriptions_owner_index');
            $table->dropColumn(['owner_type', 'owner_id']);
        });
    }
}