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
        if (! Schema::hasTable('entity_configurations')) {
            Schema::create('entity_configurations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('configurable');
                $table->unsignedBigInteger('config_variable_id');
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->foreign('config_variable_id')
                    ->references('id')
                    ->on('config_variables')
                    ->cascadeOnDelete();

                $table->unique(
                    ['configurable_type', 'configurable_id', 'config_variable_id'],
                    'entity_config_unique'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_configurations');
    }
};
