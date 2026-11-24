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
        Schema::create('journey_backdrops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->string('layer');
            $table->string('path');
            $table->unsignedInteger('position')->default(0);
            // Parallax speed: 1 moves with the boards, below 1 is slower, above 1 faster.
            $table->decimal('speed', 4, 2);
            $table->string('align')->default('bottom');
            // Share of the stage height the image fills, in percent.
            $table->unsignedTinyInteger('height')->default(100);
            $table->boolean('repeat')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journey_backdrops');
    }
};
