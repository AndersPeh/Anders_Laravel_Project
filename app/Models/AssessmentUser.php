<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

// Learn in-depth about pivot table in this link
// https://laravel.com/docs/11.x/eloquent-relationships

class AssessmentUser extends Pivot
{
    use HasFactory;

    protected $table = 'assessment_user';

    function assessment(){
        return $this->belongsTo('App\Models\Assessment');
    }

    function user(){
        return $this->belongsTo('App\Models\User');
    }

    function peerreviewgroup(){
        return $this->belongsTo('App\Models\PeerReviewGroup');
    }

    protected $fillable=[
        'assessment_id',
        'user_id',
        'score',
        'peer_review_group_id'
    ];

}
