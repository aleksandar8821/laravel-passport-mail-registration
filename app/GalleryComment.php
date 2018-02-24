<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GalleryComment extends Model
{
    return $this->belongsTo(Gallery::class);
}
