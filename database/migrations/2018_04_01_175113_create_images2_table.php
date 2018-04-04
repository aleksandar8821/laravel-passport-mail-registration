<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImages2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images2', function (Blueprint $table) {
             $table->engine = 'InnoDB';
             $table->increments('id');
             $table->string('url');
             $table->text('description')->nullable();
             $table->float('height_width_ratio', 4, 2)->nullable();
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
        Schema::dropIfExists('images2');
    }
}
