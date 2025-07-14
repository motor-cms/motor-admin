<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Motor\Core\Traits\CheckForeignKeys;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_system_prompts')) {
            Schema::table('ai_system_prompts', function (Blueprint $table) {
                if (! $this->getForeignKeyByColumns('ai_system_prompts', ['client_id'])) {
                    $table->foreign(['client_id'])->references(['id'])->on('clients')->onUpdate('no action')->onDelete('no action');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ai_system_prompts')) {
            Schema::table('ai_system_prompts', function (Blueprint $table) {
                $table->dropForeign('ai_system_prompts_client_id_foreign');
            });
        }
    }
};
