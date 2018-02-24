<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $table = 'galleries';

    protected $fillable = [
        'name', 'description','user_id',
    ];

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function comments()
    {
    	return $this->hasMany(GalleryComment::class);
    }

    public function images()
    {
    	return $this->hasMany(Image::class);
    }
}
