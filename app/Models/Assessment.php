<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    function course(){
        return $this->belongsTo('App\Models\Course');
    }

    function users(){
        return $this->belongsToMany('App\Models\User','assessment_user')->using('App\Models\AssessmentUser')
        ->withPivot('score', 'peer_review_group_id');
    }

    function reviews(){
        return $this->hasMany('App\Models\Review');
    }

    function peerreviewgroups(){
        return $this->hasMany('App\Models\PeerReviewGroup');
    }    

    protected $fillable = [
        'title',
        'type',
        'instruction',
        'no_reviews_required',
        'max_score',
        'due_date_time',
        'course_id'
    ];

}
