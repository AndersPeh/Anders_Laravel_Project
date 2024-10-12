<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeerReviewGroup extends Model
{
    use HasFactory;

    function assessment(){
        return $this->belongsTo('App\Models\Assessment');
    }

    function users(){
        return $this->hasMany('App\Models\AssessmentUser','peer_review_group_id');
    }

    protected $fillable =[
        'assessment_id',
        'name'
    ];

}
