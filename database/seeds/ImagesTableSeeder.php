<?php

use Illuminate\Database\Seeder;

class ImagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	   		\App\Gallery::all()
   		        ->each(function (App\Gallery $gallery) {
   		                 $gallery->images()->saveMany(factory(App\Image::class, 10)
   		                     ->make()
   		             			);
   		         });
    }
}
