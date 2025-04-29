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
        if (!Schema::hasTable('users_client')) {
            Schema::create('users_client', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->index('users_client_user_id_foreign');
                $table->unsignedBigInteger('client_id')->index('users_client_client_id_foreign');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_client');
    }
};
