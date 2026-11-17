<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('examen', 'deleted_at')) {
            Schema::table('examen', function (Blueprint $table) {
                $table->softDeletes('deleted_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('examen', 'deleted_at')) {
            Schema::table('examen', function (Blueprint $table) {
                $table->dropSoftDeletes('deleted_at');
            });
        }
    }
};
