<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->decimal('hourly_price', 12, 2)->nullable()->after('base_price');
            $table->decimal('extra_person_price', 12, 2)->default(0)->after('hourly_price');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('modalidad', 20)->default('noche')->after('check_out');
            $table->time('hora_entrada')->nullable()->after('modalidad');
            $table->time('hora_salida')->nullable()->after('hora_entrada');
            $table->unsignedSmallInteger('horas')->nullable()->after('hora_salida');
            $table->unsignedTinyInteger('personas_extra')->default(0)->after('guests_count');
            $table->decimal('tarifa_persona_extra', 12, 2)->default(0)->after('personas_extra');
            $table->decimal('monto_hospedaje', 12, 2)->nullable()->after('estimated_total');
            $table->decimal('monto_extra', 12, 2)->default(0)->after('monto_hospedaje');
            $table->boolean('requiere_factura')->default(false)->after('notes');
        });

        Schema::table('huespedes', function (Blueprint $table): void {
            $table->string('identificacion_path')->nullable()->after('documento');
        });

        Schema::table('pos_products', function (Blueprint $table): void {
            $table->decimal('costo', 12, 2)->default(0)->after('price');
            $table->unsignedSmallInteger('piezas_caja')->default(1)->after('costo');
            $table->decimal('bodega', 12, 2)->default(0)->after('stock_minimo');
            $table->decimal('exhibicion', 12, 2)->default(0)->after('bodega');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropColumn(['hourly_price', 'extra_person_price']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn([
                'modalidad',
                'hora_entrada',
                'hora_salida',
                'horas',
                'personas_extra',
                'tarifa_persona_extra',
                'monto_hospedaje',
                'monto_extra',
                'requiere_factura',
            ]);
        });

        Schema::table('huespedes', function (Blueprint $table): void {
            $table->dropColumn('identificacion_path');
        });

        Schema::table('pos_products', function (Blueprint $table): void {
            $table->dropColumn(['costo', 'piezas_caja', 'bodega', 'exhibicion']);
        });
    }
};
