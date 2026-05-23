<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('payment_status', 20)->default('not_required')->after('estimated_total');
            $table->decimal('deposit_amount', 12, 2)->nullable()->after('payment_status');
            $table->string('payment_reference', 120)->nullable()->after('deposit_amount');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['payment_status', 'deposit_amount', 'payment_reference', 'paid_at']);
        });
    }
};
