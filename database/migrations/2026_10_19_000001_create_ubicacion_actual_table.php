<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TEMPORAL — tabla de ubicación actual (módulo Navegación).
 *
 * Igual que con bitacora_accesos: las demás tablas salen del .sql del
 * equipo, no de migraciones. Esta se le pidió a Mike para que la agregue
 * al .sql oficial; mientras llega, esta migración la crea SOLO en tu
 * base local. Correrla con:
 *
 *     php artisan migrate --path=database/migrations/2026_10_19_000001_create_ubicacion_actual_table.php
 *
 * Guarda una sola fila por entidad (Tipo_entidad + ID_entidad) con su
 * última posición conocida — es lo que consulta el mapa para pintar
 * "dónde está cada quien ahora mismo", sin tener que buscar en todo
 * el histórico de historial_movimientos.
 *
 * Cada INSERT/UPDATE sobre esta tabla dispara un trigger que también
 * deja el registro en historial_movimientos, para no duplicar esa
 * lógica en el controlador.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ubicacion_actual')) {
            return;
        }

        Schema::create('ubicacion_actual', function (Blueprint $table) {
            $table->increments('ID_ubicacion');
            $table->string('Tipo_entidad', 100);
            $table->integer('ID_entidad');
            $table->integer('ID_isla')->nullable()->index('IDX_UbicacionActual_Isla');
            $table->decimal('Coordenada_X', 10, 6);
            $table->decimal('Coordenada_Y', 10, 6);
            $table->dateTime('Fecha_actualizacion')->useCurrent();

            $table->unique(['Tipo_entidad', 'ID_entidad'], 'UQ_UbicacionActual_Entidad');
        });

        // Los triggers son sintaxis de MySQL: en otras bases (SQLite de los tests) se omiten.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Trigger: cada vez que se inserta o actualiza una ubicación,
        // se deja también el registro correspondiente en historial_movimientos.
        DB::unprepared('
            CREATE TRIGGER trg_ubicacion_actual_insert_historial
            AFTER INSERT ON ubicacion_actual
            FOR EACH ROW
            BEGIN
                INSERT INTO historial_movimientos
                    (Tipo_entidad, ID_entidad, Coordenada_X, Coordenada_Y, Fecha_registro, Observaciones)
                VALUES
                    (NEW.Tipo_entidad, NEW.ID_entidad, NEW.Coordenada_X, NEW.Coordenada_Y, NEW.Fecha_actualizacion, "Ubicación inicial registrada");
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_ubicacion_actual_update_historial
            AFTER UPDATE ON ubicacion_actual
            FOR EACH ROW
            BEGIN
                INSERT INTO historial_movimientos
                    (Tipo_entidad, ID_entidad, Coordenada_X, Coordenada_Y, Fecha_registro, Observaciones)
                VALUES
                    (NEW.Tipo_entidad, NEW.ID_entidad, NEW.Coordenada_X, NEW.Coordenada_Y, NEW.Fecha_actualizacion, "Ubicación actualizada");
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_ubicacion_actual_insert_historial');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_ubicacion_actual_update_historial');
        Schema::dropIfExists('ubicacion_actual');
    }
};