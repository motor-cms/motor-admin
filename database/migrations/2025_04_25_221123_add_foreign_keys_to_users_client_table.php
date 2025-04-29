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
        if (Schema::hasTable('users_client')) {
            Schema::table('users_client', function (Blueprint $table) {
                $table->foreign(['client_id'])->references(['id'])->on('clients')->onUpdate('no action')->onDelete('cascade');
                $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users_client')) {
            Schema::table('users_client', function (Blueprint $table) {
                $table->dropForeign('users_client_client_id_foreign');
                $table->dropForeign('users_client_user_id_foreign');
            });
        }
    }
};
