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
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('day')->default(1);
            $table->time('time')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_night')->default(false);
            $table->string('mood')->nullable();
            $table->json('simulator_state')->nullable();
            $table->timestamps();

            $table->unique(['journey_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
