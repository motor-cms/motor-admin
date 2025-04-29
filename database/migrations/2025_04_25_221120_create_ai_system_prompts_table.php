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
        if (!Schema::hasTable('ai_system_prompts')) {
            Schema::create('ai_system_prompts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->text('prompt');
                $table->unsignedBigInteger('client_id')->nullable()->index('ai_system_prompts_client_id_foreign');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_system_prompts');
    }
};
