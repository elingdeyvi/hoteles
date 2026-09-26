<?php

use App\Models\Property;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('codigo', 30);
            $table->string('nombre', 80);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['property_id', 'codigo']);
        });

        Schema::create('aperturas_caja', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('fondo_inicial', 12, 2)->default(0);
            $table->timestamp('fecha_apertura');
            $table->timestamp('fecha_cierre')->nullable();
            $table->decimal('total_consumos', 12, 2)->default(0);
            $table->decimal('total_efectivo', 12, 2)->default(0);
            $table->decimal('total_tarjeta', 12, 2)->default(0);
            $table->decimal('total_transferencia', 12, 2)->default(0);
            $table->decimal('total_otros', 12, 2)->default(0);
            $table->decimal('total_cobros', 12, 2)->default(0);
            $table->decimal('total_esperado', 12, 2)->default(0);
            $table->decimal('total_real', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('cerrada')->default(false);
            $table->timestamps();
        });

        Schema::create('cortes_caja', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('apertura_caja_id')->constrained('aperturas_caja')->cascadeOnDelete();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 1);
            $table->timestamp('fecha_corte');
            $table->decimal('fondo_inicial', 12, 2)->default(0);
            $table->decimal('total_consumos', 12, 2)->default(0);
            $table->decimal('total_efectivo', 12, 2)->default(0);
            $table->decimal('total_tarjeta', 12, 2)->default(0);
            $table->decimal('total_transferencia', 12, 2)->default(0);
            $table->decimal('total_otros', 12, 2)->default(0);
            $table->decimal('total_cobros', 12, 2)->default(0);
            $table->decimal('total_esperado', 12, 2)->default(0);
            $table->decimal('total_real', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::table('folio_payments', function (Blueprint $table): void {
            $table->foreignId('apertura_caja_id')->nullable()->after('received_by')->constrained('aperturas_caja')->nullOnDelete();
        });

        Schema::table('folio_charges', function (Blueprint $table): void {
            $table->foreignId('apertura_caja_id')->nullable()->after('charged_by')->constrained('aperturas_caja')->nullOnDelete();
        });

        $permiso = Permission::findOrCreate('caja.operar', 'web');
        foreach (['Administrador', 'Recepcionista', 'Cajero'] as $nombre) {
            $rol = Role::query()->where('name', $nombre)->where('guard_name', 'web')->first();
            $rol?->givePermissionTo($permiso);
        }

        foreach (Property::query()->get() as $property) {
            \App\Models\Caja::query()->firstOrCreate(
                ['property_id' => $property->id, 'codigo' => 'recepcion'],
                ['nombre' => 'Recepción', 'activo' => true]
            );
        }
    }

    public function down(): void
    {
        Schema::table('folio_charges', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('apertura_caja_id');
        });
        Schema::table('folio_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('apertura_caja_id');
        });
        Schema::dropIfExists('cortes_caja');
        Schema::dropIfExists('aperturas_caja');
        Schema::dropIfExists('cajas');
    }
};
