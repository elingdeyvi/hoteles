<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_products', function (Blueprint $table): void {
            $table->boolean('controla_inventario')->default(false)->after('price');
            $table->decimal('stock_actual', 12, 2)->default(0)->after('controla_inventario');
            $table->decimal('stock_minimo', 12, 2)->default(0)->after('stock_actual');
        });

        Schema::create('pos_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_product_id')->constrained('pos_products')->cascadeOnDelete();
            $table->string('tipo', 10);
            $table->string('motivo', 20);
            $table->decimal('cantidad', 12, 2);
            $table->decimal('stock_anterior', 12, 2);
            $table->decimal('stock_nuevo', 12, 2);
            $table->foreignId('folio_charge_id')->nullable()->constrained('folio_charges')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('observaciones')->nullable();
            $table->timestamp('fecha_movimiento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_movimientos');

        Schema::table('pos_products', function (Blueprint $table): void {
            $table->dropColumn(['controla_inventario', 'stock_actual', 'stock_minimo']);
        });
    }
};
