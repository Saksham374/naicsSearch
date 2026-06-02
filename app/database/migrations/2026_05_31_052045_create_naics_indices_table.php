<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('naics_indexes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('naics_code_id')
                ->constrained('naics_codes')
                ->cascadeOnDelete();

            $table->text('index_description');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('naics_indices');
    }
};
