<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')->constrained('user_subscriptions')->cascadeOnDelete();

            $table->unsignedInteger('amount');
            $table->enum('payment_type', ['initial', 'monthly', 'installment']);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');

            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_id')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_payments');
    }
}