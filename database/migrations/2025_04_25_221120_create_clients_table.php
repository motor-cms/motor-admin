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
        if (!Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('slug');
                $table->boolean('is_active')->default(false);
                $table->string('name');
                $table->string('address')->default('');
                $table->string('zip')->default('');
                $table->string('city')->default('');
                $table->string('country_iso_3166_1')->default('de');
                $table->string('website')->default('');
                $table->string('contact_name')->default('');
                $table->string('contact_phone')->default('');
                $table->string('contact_email')->default('');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
