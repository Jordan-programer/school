<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrigir tabela pivot student_guardian:
     * Remover o UUID manual e usar geração automática via gen_random_uuid().
     */
    public function up(): void
    {
        // Adicionar default UUID à coluna id da pivot
        DB::statement('ALTER TABLE student_guardian ALTER COLUMN id SET DEFAULT gen_random_uuid()');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE student_guardian ALTER COLUMN id DROP DEFAULT');
    }
};
