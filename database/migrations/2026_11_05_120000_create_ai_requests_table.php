<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Metadata of every AI call (cost and usage per tenant). Prompt text only
     * when ai.log_prompts is on; otherwise just a hash to spot repeats.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_requests')) {
            Schema::create('ai_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('task', 50);
                $table->string('provider', 30);
                $table->string('model', 80)->nullable();
                $table->string('status', 20);
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('completion_tokens')->default(0);
                $table->decimal('cost_usd', 10, 6)->default(0);
                $table->unsignedInteger('duration_ms')->default(0);
                $table->unsignedTinyInteger('repair_rounds')->default(0);
                $table->string('error', 255)->nullable();
                $table->string('prompt_hash', 64)->nullable();
                $table->text('prompt_text')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
