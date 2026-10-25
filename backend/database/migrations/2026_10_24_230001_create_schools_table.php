<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // ex: ESC-001-LUANDA
            $table->string('name');
            $table->string('tax_id')->nullable(); // NIF angolano
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('province')->default('Luanda');
            $table->string('municipality')->default('Luanda');
            $table->string('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
