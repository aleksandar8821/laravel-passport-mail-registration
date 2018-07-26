<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDataVersion extends Model
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    protected $fillable = [
        'rollback_revoke_changes_token'
    ];
}
