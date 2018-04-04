<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateGalleryCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gallery_comments', function (Blueprint $table) {
            $table->text('comment_body');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('gallery_id');
            $table->foreign('gallery_id')->references('id')->on('galleries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gallery_comments', function (Blueprint $table) {
            $table->dropForeign('gallery_comments_user_id_foreign');
            $table->dropForeign('gallery_comments_gallery_id_foreign');
            $table->dropColumn(['comment_body', 'user_id', 'gallery_id']);
        });
    }
}
