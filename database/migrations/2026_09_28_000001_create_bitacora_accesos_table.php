<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEMPORAL — tabla de la bitácora de accesos (RNF08).
 *
 * Las demás tablas salen del .sql del equipo, no de migraciones. Esta se
 * le pidió al CTO para que la agregue al .sql; mientras llega, esta
 * migración la crea SOLO en tu base local. Correrla con:
 *
 *     php artisan migrate --path=database/migrations/2026_09_28_000001_create_bitacora_accesos_table.php
 *
 * Cuando la tabla esté en el .sql oficial, se borra este archivo.
 * Si la tabla ya existe, no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bitacora_accesos')) {
            return;
        }

        Schema::create('bitacora_accesos', function (Blueprint $table) {
            $table->increments('ID_bitacora');
            $table->integer('ID_usuario')->nullable()->index('IDX_Bitacora_Usuario');
            $table->string('Correo_intento', 150)->nullable();
            $table->string('Accion', 50);
            $table->string('IP', 45)->nullable();
            $table->string('Detalle', 255)->nullable();
            $table->dateTime('Fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_accesos');
    }
};
