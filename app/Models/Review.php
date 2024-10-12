<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    function assessment(){
        return $this->belongsTo('App\Models\Assessment');
    }

    function reviewer(){
        return $this->belongsTo('App\Models\User', 'reviewer_id');
    }

    function reviewee(){
        return $this->belongsTo('App\Models\User', 'reviewee_id');
    }

    protected $fillable = [
        'assessment_id',
        'reviewer_id',
        'reviewee_id',
        'review_text',
        'rating'
    ];



}
