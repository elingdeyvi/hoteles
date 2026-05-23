<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configuracion_empresa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa');
            $table->string('nombre_corto', 50)->nullable();
            $table->string('nombre_largo')->nullable();
            $table->string('rfc', 13)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('ciudad')->nullable();
            $table->string('estado')->nullable();
            $table->string('pais')->default('México');
            $table->string('sitio_web')->nullable();
            $table->string('logo_path')->nullable(); // Ruta del logo almacenado
            $table->string('logo_original_name')->nullable(); // Nombre original del archivo
            $table->string('logo_mime_type')->nullable(); // Tipo MIME del logo
            $table->integer('logo_size_bytes')->nullable(); // Tamano del archivo en bytes
            $table->string('favicon_path')->nullable(); // Ruta del favicon
            $table->string('color_primario', 7)->default('#0d6efd'); // Tema panel (primario)
            $table->string('color_secundario', 7)->default('#6c757d'); // Tema panel (secundario)
            $table->text('terminos_condiciones')->nullable();
            $table->text('politica_privacidad')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configuracion_empresa');
    }
};
