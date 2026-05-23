<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('timezone', 50)->default('America/Mexico_City');
            $table->string('currency', 3)->default('MXN');
            $table->string('phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->text('address')->nullable();
            $table->boolean('booking_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $tables = [
            'room_types' => ['code'],
            'rooms' => ['number'],
            'reservations' => ['folio', 'online_reference'],
            'pos_outlets' => ['code'],
        ];

        foreach ($tables as $tableName => $uniqueCols) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('property_id')->nullable()->after('id')->constrained('properties')->cascadeOnDelete();
            });
        }

        Schema::table('configuracion_empresa', function (Blueprint $table): void {
            $table->foreignId('property_id')->nullable()->after('id')->constrained('properties')->cascadeOnDelete();
        });

        $propertyId = $this->seedDefaultProperty();

        foreach (array_keys($tables) as $tableName) {
            DB::table($tableName)->whereNull('property_id')->update(['property_id' => $propertyId]);
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('property_id')->nullable(false)->change();
            });
        }

        DB::table('configuracion_empresa')->whereNull('property_id')->update(['property_id' => $propertyId]);

        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->unique(['property_id', 'code']);
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropUnique(['number']);
            $table->unique(['property_id', 'number']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropUnique(['folio']);
            $table->unique(['property_id', 'folio']);
            if (Schema::hasColumn('reservations', 'online_reference')) {
                $table->dropUnique(['online_reference']);
                $table->unique(['property_id', 'online_reference']);
            }
        });

        Schema::table('pos_outlets', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->unique(['property_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('pos_outlets', function (Blueprint $table): void {
            $table->dropUnique(['property_id', 'code']);
            $table->unique('code');
            $table->dropConstrainedForeignId('property_id');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropUnique(['property_id', 'folio']);
            $table->unique('folio');
            if (Schema::hasColumn('reservations', 'online_reference')) {
                $table->dropUnique(['property_id', 'online_reference']);
                $table->unique('online_reference');
            }
            $table->dropConstrainedForeignId('property_id');
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropUnique(['property_id', 'number']);
            $table->unique('number');
            $table->dropConstrainedForeignId('property_id');
        });

        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropUnique(['property_id', 'code']);
            $table->unique('code');
            $table->dropConstrainedForeignId('property_id');
        });

        Schema::table('configuracion_empresa', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('property_id');
        });

        Schema::dropIfExists('properties');
    }

    private function seedDefaultProperty(): int
    {
        return DB::table('properties')->insertGetId([
            'code' => 'costa-azul',
            'name' => 'Hotel Costa Azul',
            'timezone' => 'America/Mexico_City',
            'currency' => 'MXN',
            'phone' => '555-0100',
            'email' => 'recepcion@costaazul.demo',
            'address' => 'Av. del Mar 100, Playa Norte',
            'booking_enabled' => true,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
