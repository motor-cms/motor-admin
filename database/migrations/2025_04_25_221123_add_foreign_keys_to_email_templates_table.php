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
        if (Schema::hasTable('email_templates')) {
            Schema::table('email_templates', function (Blueprint $table) {

                if (! $this->getForeignKeyByColumns('email_templates', ['client_id'])) {
                    $table->foreign(['client_id'])->references(['id'])->on('clients')->onUpdate('no action')->onDelete('cascade');
                }

                if (! $this->getForeignKeyByColumns('email_templates', ['language_id'])) {
                    $table->foreign(['language_id'])->references(['id'])->on('languages')->onUpdate('no action')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('email_templates')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropForeign('email_templates_client_id_foreign');
                $table->dropForeign('email_templates_language_id_foreign');
            });
        }
    }
};
