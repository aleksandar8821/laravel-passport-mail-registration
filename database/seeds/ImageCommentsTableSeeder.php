<?php

use Illuminate\Database\Seeder;

class ImageCommentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Image::all()
   		        ->each(function (App\Image $image) {
   		                 $image->comments()->saveMany(factory(App\ImageComment::class, 2)
   		                     ->make()
   		             			);
   		         });
    }
}
