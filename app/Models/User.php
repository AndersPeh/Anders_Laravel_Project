<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    function courses(){
        return $this->belongsToMany('App\Models\Course','course_user');
    }

    function assessments(){
        return $this->belongsToMany('App\Models\Assessment','assessment_user')->using('App\Models\AssessmentUser')
        ->withPivot('score', 'peer_review_group_id');
    }

    function submittedreviews()
    {
        return $this->hasMany('App\Models\Review', 'reviewer_id');
    }

    function receivedreviews(){
        return $this->hasMany('App\Models\Review', 'reviewee_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        's_number',
        'user_type',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
