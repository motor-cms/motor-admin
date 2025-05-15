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
        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('collection_name');
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk');
                $table->unsignedInteger('size');
                $table->text('manipulations');
                $table->text('custom_properties');
                $table->json('responsive_images');
                $table->unsignedInteger('order_column')->nullable();
                $table->string('conversions_disk')->nullable();
                $table->char('uuid', 36)->nullable()->unique();
                $table->timestamps();
                $table->json('generated_conversions');

                $table->index(['model_type', 'model_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
