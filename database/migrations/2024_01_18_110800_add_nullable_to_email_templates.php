<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('default_cc_email')->nullable()->change();
            $table->string('default_bcc_email')->nullable()->change();
            $table->string('default_replyto_name')->nullable()->change();
            $table->string('default_replyto_email')->nullable()->change();
            $table->string('default_recipient_name')->nullable()->change();
            $table->string('default_recipient_email')->nullable()->change();
            $table->string('default_sender_name')->nullable()->change();
            $table->string('default_sender_email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
