
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante_foto', function (Blueprint $table) {
            $table->id('id_foto');

            $table->foreignId('id_estudiante')
                ->unique()
                ->constrained('estudiante', 'id_estudiante')
                ->cascadeOnDelete();

            // Referencia de la imagen almacenada en Cloudinary
            $table->string('cloudinary_public_id', 255);

            // Tipo de acceso utilizado en Cloudinary
            $table->string('cloudinary_type', 30)
                ->default('authenticated');

            // Resultado de la validación de la fotografía
            $table->string('estado_validacion', 20)
                ->default('pendiente');

            $table->text('motivo_rechazo')->nullable();

            $table->timestamp('fecha_subida')->useCurrent();
            $table->timestamp('fecha_validacion')->nullable();

            $table->timestamps();

            $table->index('estado_validacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_foto');
    }
};
