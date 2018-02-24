<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImageComment extends Model
{
    return $this->belongsTo(Image::class);
}
