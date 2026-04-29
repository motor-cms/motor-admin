<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `website` is the one truly optional client field that's still NOT NULL
 * in the live DB (no default either), causing 1048 integrity errors when
 * the frontend submits null. Switch it to nullable to match its optional
 * semantics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('website')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('website')->nullable(false)->change();
        });
    }
};
