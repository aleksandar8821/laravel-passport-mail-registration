<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateImageCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('image_comments', function (Blueprint $table) {
            $table->text('comment_body');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('image_id');
            $table->foreign('image_id')->references('id')->on('images')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('image_comments', function (Blueprint $table) {
            $table->dropForeign('image_comments_user_id_foreign');
            $table->dropForeign('image_comments_image_id_foreign');
            $table->dropColumn(['comment_body', 'user_id', 'image_id']);
        });
    }
}
