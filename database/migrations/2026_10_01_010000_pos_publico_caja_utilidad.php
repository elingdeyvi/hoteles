<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_ventas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('apertura_caja_id')->nullable()->constrained('aperturas_caja')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('numero', 40);
            $table->string('payment_method', 30);
            $table->decimal('total', 12, 2);
            $table->decimal('recibido', 12, 2)->nullable();
            $table->decimal('cambio', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['property_id', 'numero']);
        });

        Schema::create('pos_venta_lineas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_venta_id')->constrained('pos_ventas')->cascadeOnDelete();
            $table->foreignId('pos_product_id')->nullable()->constrained('pos_products')->nullOnDelete();
            $table->string('nombre');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio', 12, 2);
            $table->decimal('costo', 12, 2)->default(0);
            $table->decimal('importe', 12, 2);
            $table->timestamps();
        });

        Schema::create('movimientos_caja', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('apertura_caja_id')->constrained('aperturas_caja')->cascadeOnDelete();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 20);
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_movimiento');
            $table->timestamps();
        });

        Schema::table('aperturas_caja', function (Blueprint $table): void {
            $table->decimal('total_ingresos', 12, 2)->default(0)->after('total_cobros');
            $table->decimal('total_egresos', 12, 2)->default(0)->after('total_ingresos');
        });

        Schema::table('cortes_caja', function (Blueprint $table): void {
            $table->decimal('total_ingresos', 12, 2)->default(0)->after('total_cobros');
            $table->decimal('total_egresos', 12, 2)->default(0)->after('total_ingresos');
            $table->json('conteo')->nullable()->after('observaciones');
        });

        Schema::table('folio_payments', function (Blueprint $table): void {
            $table->decimal('recibido', 12, 2)->nullable()->after('amount');
            $table->decimal('cambio', 12, 2)->default(0)->after('recibido');
        });

        Schema::table('pos_products', function (Blueprint $table): void {
            $table->decimal('iva_porcentaje', 5, 2)->default(16)->after('price');
            $table->boolean('aplicar_iva')->default(false)->after('iva_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('pos_products', function (Blueprint $table): void {
            $table->dropColumn(['iva_porcentaje', 'aplicar_iva']);
        });
        Schema::table('folio_payments', function (Blueprint $table): void {
            $table->dropColumn(['recibido', 'cambio']);
        });
        Schema::table('cortes_caja', function (Blueprint $table): void {
            $table->dropColumn(['total_ingresos', 'total_egresos', 'conteo']);
        });
        Schema::table('aperturas_caja', function (Blueprint $table): void {
            $table->dropColumn(['total_ingresos', 'total_egresos']);
        });
        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('pos_venta_lineas');
        Schema::dropIfExists('pos_ventas');
    }
};
