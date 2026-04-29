<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dashboard_announcement_dismissals')) {
            Schema::create('dashboard_announcement_dismissals', function (Blueprint $table) {
                $table->unsignedBigInteger('announcement_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('dismissed_at');

                $table->primary(['announcement_id', 'user_id']);
                $table->foreign('announcement_id')
                    ->references('id')
                    ->on('dashboard_announcements')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_announcement_dismissals');
    }
};
