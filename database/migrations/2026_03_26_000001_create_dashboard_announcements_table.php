<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dashboard_announcements')) {
            Schema::create('dashboard_announcements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('type')->default('info');
                $table->string('audience')->default('self');
                $table->json('target_user_ids')->nullable();
                $table->string('linkable_type')->nullable();
                $table->unsignedBigInteger('linkable_id')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('client_id')->index();
                $table->bigInteger('created_by')->nullable();
                $table->bigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['linkable_type', 'linkable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_announcements');
    }
};
