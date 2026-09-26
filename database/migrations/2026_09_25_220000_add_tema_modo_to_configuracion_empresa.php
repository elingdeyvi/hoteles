<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracion_empresa', 'tema_modo')) {
                $table->string('tema_modo', 20)->default('claro')->after('color_secundario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracion_empresa', 'tema_modo')) {
                $table->dropColumn('tema_modo');
            }
        });
    }
};
