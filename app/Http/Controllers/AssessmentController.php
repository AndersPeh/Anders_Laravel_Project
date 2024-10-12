<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseUser;
use App\Models\Review;
use App\Models\PeerReviewGroup;
use App\Models\AssessmentUser;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
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
    public function create(string $id)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Retrieve course data
            $course = Course::find($id);

            return view("assessments.create")->with("course",$course)->with("user",$user);
                      
        } else {

            // If user is not authenticated
            return view("register_login");
        }
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

            // Validate inputs of teacher to ensure they fulfill assignment criteria
            $request->validate([
                "title"=>"required|max:20",
                "type"=>"required",
                "instruction" => "required|string",
                "no_reviews_required"=>"required|integer|min:1",
                "max_score"=>"required|numeric|min:1|max:100",
                "due_date_time"=>"required|date"
            ]);

            // Retrieve course data
            $course = Course::find($request->course_id);

            // Store new assessment
            $assessment = new Assessment();
            $assessment->title = $request->title;
            $assessment->type = $request->type;
            $assessment->instruction = $request->instruction;
            $assessment->no_reviews_required = $request->no_reviews_required;
            $assessment->max_score = $request->max_score;
            $assessment->due_date_time = $request->due_date_time;
            $assessment->course_id = $request->course_id;
            $assessment->save();

            // link students to this assessment
            $students = $course->users()->where('user_type', 'student')->get();
            
            // set student_ids array to append from forloop
            $student_ids = [];
            
            foreach ($students as $student) {
                $student_ids[] = $student->id;
            }
            
            // attach student IDs to this assessment
            $assessment->users()->attach($student_ids);
            
            // when redirect, need to use session to retrieve data later in blade
            return redirect()->route('course.show', $course->id)->with('added', 'New assessment has been added.');

        } else {

            // If user is not authenticated
            return view("register_login");
        }    
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Find the assessment using assessment id when the user clicks an assessment
            $assessment=Assessment::find($id);         
            
            // Retrieve the course for this assessment using the course method in assessment model
            $course=$assessment->course;  

            // Retrieve updated message of update from the session
            $updated = session('updated');

            // Check if there is any submission for this assessment
            $submission = Review::where('assessment_id', $id)->get();

            // for teacher, retrieve students enrolled in this course by accessing users() in Course model 
            // with query where user_type is student to ignore teacher.
            if($user->user_type=="teacher"){

                // Retrieve all students taking this course by accessing users method under course model
                $students = $course->users()->where("user_type","student")->get();

                // to store students reviews information
                $studentsreviews=[];

                foreach($students as $student){
                    // Retrieves submitted reviews by accessing submittedreviews method in user model where assessment_id is current assessment
                    $submitted_reviews= $student->submittedreviews()->where("assessment_id", $assessment->id)->get();

                    // Retrieves submitted reviews by accessing submittedreviews method in user model where assessment_id is current assessment
                    $received_reviews= $student->receivedreviews()->where("assessment_id", $assessment->id)->get();
                
                    // count submitted reviews
                    $no_submittedreviews=count($submitted_reviews);

                     // count received reviews
                     $no_receivedreviews=count($received_reviews);
                     
                     // Retrieve score of the student for this assessment by accessing users method in assessment model
                     $score = $assessment->users()->where("user_id",$student->id)->first();

                     // retrieve score from pivot table assessment_user, if no score, then it is set as null
                     if($score){
                        $studentscore=$score->pivot->score;
                     }else{
                        $studentscore=null;
                     }
                    
                     // store the data in studentsreviews array for displaying in assessment details page of teacher
                     $studentsreviews[]=[
                        "student"=>$student,
                        "no_submittedreviews"=>$no_submittedreviews,
                        "no_receivedreviews"=>$no_receivedreviews,
                        "score"=>$studentscore
                     ];
                }

                return view("assessments.show")->with("course",$course)->with("user",$user)->with("assessment",$assessment)
                ->with("students",$students)->with("updated",$updated)->with("submission",$submission)
                ->with("studentsreviews",$studentsreviews);
            
            // for student
            }else{

                // Retrieves submitted reviews by accessing submittedreviews method in user model where assessment_id is current assessment and authenticated user id is the reviewer(means student gives review)
                $submitted_reviews= $user->submittedreviews()->where("assessment_id", $assessment->id)->where("reviewer_id", $user->id)->get();
                
                // count submitted reviews
                $no_reviews=count($submitted_reviews);

                // Retrieves received reviews by accessing receivedreviews method in user model where assessment_id is current assessment and authenticated user id is the reviewee(means student gets review)
                $received_reviews= $user->receivedreviews()->where("assessment_id", $id)->where("reviewee_id", $user->id)->get();

                // if the assessment is student-select, student needs to have all students name to choose from
                // set reviewees=null, if assessment type if student select, reviewees accesses users method of course model where user type is student
                // and user id is not authenticated student user id (want to get other students only)

                $reviewees=null;

                if($assessment->type=="student-select"){
                    
                    // if assessment type is student select, reviewees are student selected students
                    $reviewees=$course->users()->where("user_type","student")->where("users.id","!=",$user->id)->get();
                }else{

                    // retrieve user data through assessments method where assessments id match
                    $assessment_user = $user->assessments()->where("assessments.id", $assessment->id)->first();

                    // retrieve group data that matches group id
                    $group=PeerReviewGroup::find($peer_review_group_id);

                    // retrieve group's assessment_user data that matches group id
                    $group_assessment_users=AssessmentUser::where("peer_review_group_id", $peer_review_group_id)
                    ->with("user")->get();

                    // create empty array for future use
                    $reviewees_array=[];

                    // for each group's assessment_user, exclude the authenticated student, add the students to reviewees array
                    foreach($group_assessment_users as $group_assessment_user){

                        if($group_assessment_user->user_id != user->id){
                            $reviewees_array[]=$group_assessment_user->user;
                        }
                    }

                    // need to convert array to collection for Laravel use
                    $reviewees = collect($reviewees_array);

                }

                return view("assessments.show")->with("course",$course)->with("user",$user)->with("assessment",$assessment)
                ->with("submitted_reviews",$submitted_reviews)->with("received_reviews",$received_reviews)->with("reviewees",$reviewees)
                ->with("no_reviews",$no_reviews);
                }

        }else{

            // If user is not authenticated
            return view("register_login");
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Find the assessment using assessment id when the user clicks update assessment
            $assessment=Assessment::find($id);

            // Retrieve the course for this assessment using the course method in assessment model
            $course=$assessment->course;  

            return view("assessments.edit")->with("user",$user)->with("assessment",$assessment)->with("course",$course);
        }else{

            // If user is not authenticated
            return view("register_login");
        }     
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if(Auth::check()){
            // If user is authenticated
            // Validate inputs of teacher to ensure they fulfill assignment criteria
            $request->validate([
                "title"=>"required|max:20",
                "type"=>"required",
                "instruction" => "required|string",
                "no_reviews_required"=>"required|integer|min:1",
                "max_score"=>"required|numeric|min:1|max:100",
                "due_date_time"=>"required|date"
            ]);

            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Find the assessment data using assessment id through assessment model
            $assessment=Assessment::find($id);          

            // updated variable for update of assessment
            $updated=null;

            // Save the updated data to assessment table
            $assessment->title = $request->title;
            $assessment->type=$request->type;
            $assessment->instruction=$request->instruction;
            $assessment->no_reviews_required=$request->no_reviews_required;
            $assessment->max_score=$request->max_score;
            $assessment->due_date_time=$request->due_date_time;
            $assessment->save();

            // Redirect the user back with a done message.
            $updated="You have updated $assessment->title.";
            return redirect()->route("assessment.show",$assessment->id)->with("updated", $updated);
        
        }else{

            // If user is not authenticated
            return view("register_login");
        }
    }

    /**
     * Display the marking page.
     */
    public function marking(string $id)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Retrieve the assessment data through assessment model
            $assessment = Assessment::find($id);

            // Retrieve the course for this assessment using the course method in assessment model
            $course=$assessment->course;  

            // Retrieve students through users method in course model where user type is student and paginate result to 10
            $students = $course->users()->where('user_type', 'student')->paginate(10);

            return view('assessments.marking')->with('assessment', $assessment)->with('course', $course)->with('user', $user)
            ->with('students', $students);

        } else {

            // If user is not authenticated
            return view("register_login");
        }        
    }

    /**
     * processes the assign group feature.
     */
    public function assign(Request $request, string $id)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Retrieve assessment data that matches with id
            $assessment = Assessment::find($id);

            // Retrieve course using course method in assessment model
            $course = $assessment->course;

            // retrieve current peer review groups for this assessment
            $existing_groups = $assessment->peerreviewgroups;

            foreach ($existing_groups as $group) {

                // set id to null for every group
                AssessmentUser::where('peer_review_group_id', $group->id)
                    ->update(['peer_review_group_id' => null]);
    
                // Delete the group
                $group->delete();
            }

            // Retrieve all students in the course using users method in course model
            $students = $course->users()->where("user_type", "student")->get();

            // Shuffle students to randomize them
            $students = $students->shuffle();

            // group is divided to 3 students in a group unless the total number of students is not divisible by 3
            $size = 3;
            if($students->count() % 3 != 0){
                $size = 2;
            }

            // Split students into groups
            $studentgroups = $students->split($size);

            // set default group number as 1
            $group_no=1;

            // after splitting students into group of 3, for each group, add it into peer review group
            foreach($studentgroups as $studentgroup){
                $group = new PeerReviewGroup();
                $group->assessment_id = $assessment->id;
                $group->name = "Group $group_no";
                $group->save();

                // for each student in the group, I retrieve his data in assessment_user
                /// then i update the peer_review_group_id in assessment_user table
                foreach($studentgroup as $student){

                    // create record if no record matches or retrieve first data that matches the condition
                    $assessment_user= AssessmentUser::firstOrCreate(
                        ["assessment_id"=> $assessment->id, "user_id"=>$student->id],
                        ["score"=>null]
                    );

                    // assign peer review group id to the student
                    $assessment_user->peer_review_group_id = $group->id;
                    $assessment_user->save();

                }
                // group number will keep increasing after each loop
                $group_no++;
            }

            return redirect()->route("assessment.show", $assessment->id)->with("success", "Students have been successfully assigned into different groups.");
        
        } else {

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
