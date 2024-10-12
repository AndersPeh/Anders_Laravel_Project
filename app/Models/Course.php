<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    function users(){
        return $this->belongsToMany('App\Models\User','course_user');
    }

    function assessments(){
        return $this->hasMany('App\Models\Assessment');
    }
}
