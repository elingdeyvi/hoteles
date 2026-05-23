<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->json('amenities')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('number', 20)->unique();
            $table->unsignedTinyInteger('floor')->default(1);
            $table->string('status', 30)->default('disponible');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('room_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->boolean('is_weekend')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('season_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_rate_id')->constrained('room_rates')->cascadeOnDelete();
            $table->string('season_name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('price_override', 12, 2)->nullable();
            $table->decimal('multiplier', 5, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('huespedes', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('email')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('documento', 80)->nullable();
            $table->string('nacionalidad', 80)->nullable();
            $table->text('direccion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('folio', 30)->unique();
            $table->foreignId('huesped_id')->constrained('huespedes');
            $table->foreignId('room_type_id')->constrained('room_types');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('guests_count')->default(1);
            $table->string('status', 30)->default('pendiente');
            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('status', 30)->default('activa');
            $table->timestamps();
        });

        Schema::create('folios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stay_id')->constrained('stays')->cascadeOnDelete();
            $table->string('folio_number', 30)->unique();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('status', 20)->default('abierto');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('folio_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folio_id')->constrained('folios')->cascadeOnDelete();
            $table->string('concept');
            $table->string('charge_type', 30)->default('habitacion');
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('folio_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folio_id')->constrained('folios')->cascadeOnDelete();
            $table->string('payment_method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $permissions = [
            'dashboard.ver',
            'hotel.configurar',
            'recepcion.reservas',
            'recepcion.checkin',
            'recepcion.checkout',
            'recepcion.huespedes',
            'facturacion.folios',
            'facturacion.pagos',
            'housekeeping.gestionar',
            'reportes.ver',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $recepcionista = Role::where('name', 'Recepcionista')->where('guard_name', 'web')->first();
        if ($recepcionista) {
            $recepcionista->givePermissionTo([
                'dashboard.ver',
                'recepcion.reservas',
                'recepcion.checkin',
                'recepcion.checkout',
                'recepcion.huespedes',
                'facturacion.folios',
                'facturacion.pagos',
                'reportes.ver',
            ]);
        }

        $housekeeping = Role::firstOrCreate(['name' => 'Housekeeping', 'guard_name' => 'web']);
        $housekeeping->givePermissionTo(['dashboard.ver', 'housekeeping.gestionar']);

    }

    public function down(): void
    {
        Schema::dropIfExists('folio_payments');
        Schema::dropIfExists('folio_charges');
        Schema::dropIfExists('folios');
        Schema::dropIfExists('stays');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('huespedes');
        Schema::dropIfExists('season_rates');
        Schema::dropIfExists('room_rates');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');

        $permissions = [
            'hotel.configurar',
            'recepcion.reservas',
            'recepcion.checkin',
            'recepcion.checkout',
            'recepcion.huespedes',
            'facturacion.folios',
            'facturacion.pagos',
            'housekeeping.gestionar',
        ];
        Permission::whereIn('name', $permissions)->delete();
    }
};
