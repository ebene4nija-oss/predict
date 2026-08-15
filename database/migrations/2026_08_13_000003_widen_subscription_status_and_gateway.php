<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The status and gateway columns were enums fixed at their launch-day values,
 * and the application had already outgrown both:
 *
 *  - the expiry sweep needs an `expired` status, distinct from a user-initiated
 *    `cancelled`, so dunning outcomes stay separable in reporting;
 *  - admin-granted access writes gateway `admin_grant`, which the enum rejected
 *    outright — granting a comped subscription failed on the insert.
 *
 * Both become plain strings. Enum sets have to be migrated for every new value
 * and behave differently on each driver; the allowed values are enforced in the
 * application, where they are already validated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
            $table->string('gateway')->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'past_due', 'cancelled'])->default('active')->change();
            $table->enum('gateway', ['flutterwave', 'paypal'])->change();
        });
    }
};
