<?php

use Illuminate\Database\Seeder;

class GalleryCommentsTableSeeder extends Seeder
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
   		                 $gallery->comments()->saveMany(factory(App\GalleryComment::class, 2)
   		                     ->make()
   		             			);
   		         });
    }
}
