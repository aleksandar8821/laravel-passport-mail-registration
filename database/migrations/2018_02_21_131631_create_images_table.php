<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
              $table->engine = 'InnoDB';
              $table->increments('id');
              $table->string('url');
              $table->text('description')->nullable();
              $table->boolean('vertical')->default(false);
              $table->unsignedInteger('gallery_id');
              $table->foreign('gallery_id')->references('id')->on('galleries')->onDelete('cascade');
              $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('images');
    }
}
