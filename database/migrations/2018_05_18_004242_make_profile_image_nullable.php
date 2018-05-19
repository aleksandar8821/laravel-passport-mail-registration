<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeProfileImageNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Ovu foru nasao ovde https://stackoverflow.com/questions/33035406/make-column-not-nullable-in-a-laravel-5-migration/35122907
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image')->nullable(false)->change();
        });
    }
}
