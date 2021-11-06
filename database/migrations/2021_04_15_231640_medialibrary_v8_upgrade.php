<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MedialibraryV8Upgrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('order_column');
            $table->string('conversions_disk')->nullable()->after('order_column');
        });

        Media::cursor()->each(
            fn (Media $media) => $media->update(['uuid' => Str::uuid(), 'conversions_disk' => $media->disk])
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('uuid');
            $table->dropColumn('conversions_disk');
        });
    }
}
