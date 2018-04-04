<?php
namespace App\My_custom_files;

use Carbon\Carbon;

trait unixTimestampFromDate
{
    public function getCreatedAtAttribute($date){
        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->timestamp * 1000;
    }

    public function getUpdatedAtAttribute($date){
        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->timestamp * 1000;
    }
}
