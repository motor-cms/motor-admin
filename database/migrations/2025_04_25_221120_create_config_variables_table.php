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
        if (!Schema::hasTable('config_variables')) {
            Schema::create('config_variables', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('package');
                $table->string('group');
                $table->string('name');
                $table->mediumText('value');
                $table->boolean('is_invisible')->default(false);
                $table->timestamps();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_variables');
    }
};
