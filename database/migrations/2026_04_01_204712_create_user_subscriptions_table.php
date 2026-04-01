<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();

            $table->enum('status', [
                'pending',
                'active',
                'paused',
                'expired',
                'cancelled',
                'payment_overdue'
            ])->default('pending');

            $table->enum('payment_mode', ['one_time', 'monthly', 'installment'])->default('one_time');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('next_payment_date')->nullable();

            $table->unsignedInteger('visits_left')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->unsignedInteger('freeze_days_left')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_subscriptions');
    }
}