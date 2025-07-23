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
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {

                if (! $this->getForeignKeyByColumns('permissions', ['permission_group_id'])) {
                    $table->foreign(['permission_group_id'])->references(['id'])->on('permission_groups')->onUpdate('no action')->onDelete('set null');
                }

            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropForeign('permissions_permission_group_id_foreign');
            });
        }
    }
};
