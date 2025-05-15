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
        if (! Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('language_id')->nullable()->index();
                $table->string('name');
                $table->string('slug')->nullable()->unique();
                $table->string('subject');
                $table->text('body_text')->nullable();
                $table->text('body_html')->nullable();
                $table->string('default_sender_name')->nullable();
                $table->string('default_sender_email')->nullable();
                $table->string('default_recipient_name')->nullable();
                $table->string('default_recipient_email')->nullable();
                $table->string('default_cc_email')->nullable();
                $table->string('default_bcc_email')->nullable();
                $table->string('default_replyto_name')->nullable();
                $table->string('default_replyto_email')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->boolean('has_body_html')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
