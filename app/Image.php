<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{

		protected $fillable = [
		    'url', 'description',
		];

    public function gallery()
    {
    	return $this->belongsTo(Gallery::class);
    }

    public function comments()
    {
    	return $this->hasMany(ImageComment::class);
    }
}
