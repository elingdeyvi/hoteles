<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impresoras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->string('nombre_sistema', 120)->nullable();
            $table->string('ip', 45)->nullable();
            $table->unsignedSmallInteger('puerto')->default(9100);
            $table->unsignedTinyInteger('ancho_papel')->default(80);
            $table->boolean('activo')->default(true);
            $table->boolean('es_default')->default(false);
            $table->timestamps();
        });

        Schema::create('print_agent_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->string('token_hash', 64)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('impresora_id')->nullable()->constrained('impresoras')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignId('folio_id')->nullable()->constrained('folios')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('estado', 20)->default('pending');
            $table->longText('contenido');
            $table->unsignedTinyInteger('columnas')->default(42);
            $table->json('impresora_snapshot')->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->unsignedTinyInteger('max_intentos')->default(3);
            $table->text('error_mensaje')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'estado']);
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
        Schema::dropIfExists('print_agent_tokens');
        Schema::dropIfExists('impresoras');
    }
};
