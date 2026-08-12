<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('gateway', ['flutterwave', 'paypal']);
            $table->string('gateway_subscription_id')->nullable();
            $table->enum('status', ['active', 'past_due', 'cancelled'])->default('active');
            $table->string('plan')->default('monthly_pro');
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
