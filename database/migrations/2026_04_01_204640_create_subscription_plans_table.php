<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPlansTable extends Migration
{
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['yearly', 'free_visit', 'limited', 'family', 'daytime']);
            $table->unsignedInteger('duration_months')->default(1);
            $table->unsignedInteger('monthly_price')->default(0);
            $table->unsignedInteger('full_price')->default(0);
            $table->unsignedInteger('visit_limit')->nullable();
            $table->boolean('allows_installment')->default(false);
            $table->boolean('allows_monthly_payment')->default(false);
            $table->boolean('auto_renew_available')->default(false);
            $table->unsignedInteger('freeze_days_per_year')->default(0);
            $table->boolean('includes_court_booking')->default(true);
            $table->boolean('includes_trainings')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_plans');
    }
}