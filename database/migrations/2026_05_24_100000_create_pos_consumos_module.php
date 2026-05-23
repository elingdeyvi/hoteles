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
        Schema::create('pos_outlets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_outlet_id')->constrained('pos_outlets')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_category_id')->constrained('pos_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku', 40)->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('folio_charges', function (Blueprint $table): void {
            $table->foreignId('pos_product_id')->nullable()->after('charge_type')->constrained('pos_products')->nullOnDelete();
            $table->foreignId('charged_by')->nullable()->after('quantity')->constrained('users')->nullOnDelete();
        });

        $permissions = ['pos.vender', 'pos.catalogo'];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $recepcionista = Role::where('name', 'Recepcionista')->where('guard_name', 'web')->first();
        if ($recepcionista) {
            $recepcionista->givePermissionTo(['pos.vender']);
        }
    }

    public function down(): void
    {
        Schema::table('folio_charges', function (Blueprint $table): void {
            $table->dropForeign(['pos_product_id']);
            $table->dropForeign(['charged_by']);
            $table->dropColumn(['pos_product_id', 'charged_by']);
        });

        Schema::dropIfExists('pos_products');
        Schema::dropIfExists('pos_categories');
        Schema::dropIfExists('pos_outlets');

        Permission::whereIn('name', ['pos.vender', 'pos.catalogo'])->delete();
    }
};
