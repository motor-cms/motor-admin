<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-tenant AI switch ({ enabled, tone_prompt_id }). NULL means off: the
     * shared test backend also serves energis, so nothing is on by default.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('clients', 'ai_config')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->json('ai_config')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'ai_config')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('ai_config');
            });
        }
    }
};
