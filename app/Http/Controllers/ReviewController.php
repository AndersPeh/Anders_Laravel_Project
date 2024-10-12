<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseUser;
use App\Models\Review;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // When a student submited review_text, it gets validated
            $request->validate([
                'review_text' => 'required|string|min:5'
            ]);       

            // retrieve the assessment data by accessing assessment model that matches current assessment id
            $assessment = Assessment::find($request->assessment_id);

            // if assessment type if teacher assign, get the reviewee data, then access peerreviewgroup method of assessment_user model
            // to get the group that the student belongs to. 
            // Check if the reviewee exists in reviewer_group. if not, the student cannot review this student from another group
            if($assessment->type=="teacher-assign"){

                $reviewee=User::find($request->reviewee_id);

                $reviewer_group = $user->peerreviewgroup()->where("assessment_id", $assessment->id)->get();

                $samegroup = $reviewer_group->users()->where("user_id", $reviewee->id)->exists;

                if(!$samegroup){
                    return redirect()->route("assessment.show", $assessment->id)->withErrors(["reviewee_id"=>"You cannot review student in different group."]);
                }

            }

            // Retrieve the course for this assessment using the course method in assessment model
            $course=$assessment->course;  

            // count submitted reviews
            $submitted_reviews= $user->submittedreviews()->where("assessment_id", $assessment->id)->where("reviewer_id", $user->id)->get();
            $no_reviews=count($submitted_reviews);

            // Student cannot review the same reviewee so I check if the reviewee_id exists in the review for this assessment for this student
            $alreadyreview = Review::where("assessment_id", $assessment->id)->where("reviewer_id", $user->id)->where("reviewee_id", $request->reviewee_id)->get();

            // If already review before
            if(count($alreadyreview)>0){
                return redirect()->route("assessment.show", $assessment->id)->withErrors(['reviewee_id' => 'You cannot review the same student again.'])->withInput()->with("no_reviews",$no_reviews);
            }

            // make sure the student cannot submit after meeting the number of reviews required. No need to retain current data as the student can't submit review anymore.
            if($no_reviews>=$assessment->no_reviews_required){
                return redirect()->route("assessment.show", $assessment->id)->withErrors(['review_text' => 'You have already met the number of reviews required.'])->with("no_reviews",$no_reviews);
            }

            // Store the review in review table
            $review = new Review();
            $review->assessment_id = $assessment->id;
            $review->reviewer_id = $user->id;
            $review->reviewee_id = $request->reviewee_id;
            $review->review_text = $request->review_text;
            $review->save();

            $no_reviews+=1;

            return redirect()->route("assessment.show", $assessment->id)->with('submitted', 'Your peer review has been submitted.')->with("no_reviews",$no_reviews);

        } else {

            // If user is not authenticated
            return view("register_login");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($assessment_id, $student_id)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();
            if($user->user_type=="teacher"){

                // Retrieve student data using student_id
                $student = User::find($student_id);

                // Retrieve this assessment data using assessment_id
                $assessment = Assessment::find($assessment_id);

                // Retrieve the student reviews submitted for this assessment by accessing assessment method under user model
                $reviewssubmitted = $student->submittedreviews()->where("assessment_id",$assessment->id)->where("reviewer_id",$student->id)->get();

                // Retrieve the student reviews recceived for this assessment by accessing assessment method under user model
                $reviewsreceived = $student->receivedreviews()->where("assessment_id",$assessment->id)->where("reviewee_id",$student->id)->get();
            
                // retrieve the course data using course method in assessment model
                $course = $assessment->course;
                
                // Retrieve score of the student for this assessment by accessing users method in assessment model
                $score = $assessment->users()->where("user_id",$student->id)->first();

                // retrieve score from pivot table assessment_user, if no score, then it is set as null
                if($score){
                    $studentscore=$score->pivot->score;
                }else{
                    $studentscore=null;
                }

                return view("reviews.show")->with("reviewssubmitted",$reviewssubmitted)
                ->with("reviewsreceived",$reviewsreceived)->with("user",$user)->with("assessment",$assessment)->with("student",$student)
                ->with("score",$studentscore)->with("course",$course);

            }

        } else {

            // If user is not authenticated
            return view("register_login");
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Retrieve the assessment data using assessment id passed from the form
            $assessment = Assessment::find($request->assessment_id);

            // Retrieve the student data using student id passed from the form
            $student=User::find($request->student_id);

            //  Retrieve course data using course method in assessment model
            $course=$assessment->course;

            // Validate score assigned by teacher to ensure it fulfills assignment criteria
            $request->validate([
                "score"=>"required|numeric|min:0"
            ]);

            // if score given is higher than max score, error message will be displayed
            if($assessment->max_score < $request->score){    
                return redirect()->route("assessment.marking",$assessment->id)->withErrors(["errors"=>"Assigned score cannot exceed maximum score."])->withInput();
            }

            // Retrieve the score which is passed from the form
            $score=$request->score;

            // if the student is not attached to the assessment, it will attach the student and update the pivot table with new score.
            $assessment->users()->syncWithoutDetaching([$student->id => ["score" => $score]]);

            return redirect()->route("assessment.marking", $assessment->id)
            ->with("graded","You have successfully assigned score to {$student->name} for this assessment.");

        }else{

            // If user is not authenticated
            return view("register_login");
        }        
    }

    /**
    * For student to rate the reviewer
    */
   public function rate(Request $request, $id)
   {
       if(Auth::check()){
           // If user is authenticated
           // Retrieve the authenticated user which is the instance of User model
           $user = Auth::user();

           // Retrieve the review being rated
           $review = Review::find($id);

           // Validate the rating
           $request->validate([
                "rating"=> "required|integer|min:1|max:5"
           ]);

           // Update the review with the rating
           $review->rating=$request->rating;
           $review->save();

           return back()->with("rated", "You have successfully rated the review.");


        }else{

            // If user is not authenticated
            return view("register_login");
        }        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
