<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseUser;
use App\Models\Assessment;
use App\Models\Review;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;


class CourseController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Retrieve the courses of the user by accessing the courses function in User model
            $courses = $user->courses;

            $user_name = $user->name;
            $user_type = $user->user_type;            

            // Pass the courses enrolled by the user to view
            return view("courses.index")->with("courses",$courses)->with("user_name",$user_name)->with("user_type",$user_type);
        } else {

            // If user is not authenticated
            return view("register_login");
        }
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
        //
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

            // Find the course using course id when the user clicks a course
            $course=Course::find($id);         
            
            // Retrieve all assessments for this course using the assessments method in course model
            $assessments=$course->assessments;  

            // for teacher, retrieve students enrolled in this course by accessing users() in Course model 
            // with query where user_type is student to ignore teacher.
            if($user->user_type=="teacher"){

                $students = $course->users()->where("user_type","student")->get();
                return view("courses.show")->with("course",$course)->with("user",$user)->with("students",$students)->with("assessments",$assessments);
            
            // for student, retrieve teachers enrolled in this course by accessing users() in Course model 
            // with query where user_type is teacher to ignore student.
            }else{

                $teachers = $course->users()->where("user_type","teacher")->get();
                return view("courses.show")->with("course",$course)->with("user",$user)->with("teachers",$teachers)->with("assessments",$assessments);
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

            // Find the course using course id when the user clicks enroll student
            $course=Course::find($id);

            // Retrieve done message of update from the session
            $done = session('done');

            return view("courses.enroll")->with("user",$user)->with("course",$course)->with("done",$done);
        }else{

            // If user is not authenticated
            return view("register_login");
        }
    }

    /**
     * Update the specified resource in storage.
     * I learn more about redirecting from below
     * https://laravel.com/docs/11.x/redirects
     */
    public function update(Request $request, string $id)
    {
        if(Auth::check()){
            // If user is authenticated
            // s_number input is compulsory and must exist in users table
            $request->validate([
                "s_number"=>"required|string"
            ]);

            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Find the course submitted
            $course=Course::find($id);

            // Find the student provided using student_id
            $student=User::where('s_number',$request->s_number)->first();

            // If student not found in database, error message is displayed.
            if(!$student){
                return redirect()->route("course.edit",$course->id)->withErrors(["errors"=>"Student entered does not exist and please ensure you start s_number with 's'."])->withInput();
            }

            // Make sure student enrolled cannot be enrolled again
            $enrolled_student= $course->users()->where("user_id", $student->id)->first();

            // done variable for course enrollment
            $done=null;

            // if the student has already enrolled, appropriate error message will be displayed and input will be retained for the teacher to modify.
            // Because input will not be stored automatically unless I use validate
            if($enrolled_student){    
                return redirect()->route("course.edit",$course->id)->withErrors(["errors"=>"Enrolled student cannot be enrolled again."])->withInput();
            }

            // Enroll the student to the course in Course_User table
            DB::table("course_user")->insert([
                "user_id"=>$student->id,
                "course_id"=>$course->id
            ]);

            // retrieve assessments data
            $assessments = $course->assessments;

            // link student id to each assessment
            foreach($assessments as $assessment){
                $assessment->users()->attach($student->id);
            }

            // Redirect the user back with a done message.
            $done="You have added $student->name ($student->s_number) to $course->course_code: $course->name.";
            return redirect()->route("course.edit",$course->id)->with("done", $done);
        
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

    /**
     * For displaying upload form.
     */
    public function uploadform()
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            return view("courses.upload")->with("user",$user);

        }else{

            // If user is not authenticated
            return view("register_login");
        }
    }

    /**
     * For uploading course information.
     * For the file reading, I learn from the websites below
     * https://ashallendesign.co.uk/blog/reading-json-files-in-laravel
     * https://laravel.com/docs/4.2/requests#files
     */

    public function uploadcourseinformation(Request $request)
    {
        if(Auth::check()){
            // If user is authenticated
            // Retrieve the authenticated user which is the instance of User model
            $user = Auth::user();

            // Teacher must submit a file when presses upload button
            $request->validate([
                "course_file"=>"required"
            ]);

            // Retrieve the uploaded file
            $file= $request->file("course_file");

            // Read the JSON file
            $course_info=file_get_contents($file->getRealPath());

            //access to json data
            $info = json_decode($course_info,true);

            // check if course code is in the database by accessing course model where course_code is the uploaded course code
            $exist = Course::where("course_code", $info["course_code"])->first();

            // If course with the same course code is added, it displays error message
            if($exist) {
                return back()->withErrors(['Course with the same course code cannot be added.']);
            }
    
            // create new course in the course table
            $course = new Course();
            $course->course_code = $info['course_code'];
            $course->name = $info['course_name'];
            $course->save();

            foreach ($info['s_number_teachers'] as $s_number) {
  
                // Find the teacher using s_number by accessing user model where s_number is the uploaded s_number
                $teacher = User::where('s_number', $s_number)->first();

                // assign the teacher to this course
                DB::table('course_user')->insert([
                    'user_id' => $teacher->id,
                    'course_id' => $course->id
                ]);
            }

            foreach ($info['s_number_students'] as $s_number) {

                // Find the student using s_number by accessing user model where s_number is the uploaded s_number
                $student = User::where('s_number', $s_number)->first();

                if (!$student) {
                    // If student cannot be found, skip. otherwise it will make my website error.
                    continue;
                }

                // if student can be found in the database, assign student to the course
                DB::table('course_user')->insert([
                    'user_id' => $student->id,
                    'course_id' => $course->id
                ]);
            }

            // Create assessments for this course
            foreach ($info['assessments'] as $assessment_info) {
                $assessment = new Assessment();
                $assessment->title = $assessment_info['title'];
                $assessment->type = $assessment_info['type'];
                $assessment->instruction = $assessment_info['instruction'];
                $assessment->no_reviews_required = $assessment_info['no_reviews_required'];
                $assessment->max_score = $assessment_info['max_score'];
                $assessment->due_date_time = $assessment_info['due_date_time'];
                $assessment->course_id = $course->id;
                $assessment->save();

                // find all students taking this course
                $students = $course->users()->where('user_type', 'student')->get();

                // for storing ids later
                $student_ids = [];
                
                // extract id from every student
                foreach ($students as $student) {
                    $student_ids[] = $student->id;
                }
                
                // attach extracted student ids to this assessment
                $assessment->users()->attach($student_ids);
                
            }
            
            return redirect()->route('course.show', $course->id)->with('added', 'Course information has been successfully added.');


        }else{

            // If user is not authenticated
            return view("register_login");
        }
    }

    /**
     * For displaying top 5 reviewers.
     * I learn more about laravel methods here 
     * https://laravel.com/docs/11.x/collections 
     */

     public function topreviewers($id)
     {
         if(Auth::check()){
             // If user is authenticated
             // Retrieve the authenticated user which is the instance of User model
             $user = Auth::user();

             // Retrieve the course by finding course that matches the given id
             $course = Course::find($id);
             
            // Get assessments of the course
            $assessments = $course->assessments()->get();

            // set assessment ids variable to collect from for loop
            $assessmentids = [];
            
            // set variable for reviewer ratings as collection so I can use sort, avg etc. later
            $reviewer_ratings = collect();            
            
            // put assessment IDs of each assessment to assessmentids
            foreach ($assessments as $assessment) {
                $assessmentids[] = $assessment->id;
            }

            // Retrieve reviews with ratings for assessments of this couse
            $reviews = Review::whereIn("assessment_id", $assessmentids)->whereNotNull("rating")->get();

            // Put all reviews by same reviewer_id together, it can be used as review_group later
            $reviews_reviewer = $reviews->groupBy('reviewer_id');

            // For each reviewer, I access the review_group which contains all their reviews using their id, 
            // calculate average rating for their reviews in their own group
            foreach ($reviews_reviewer as $reviewer_id => $review_group) {

                $average_rating = $review_group->avg('rating');

                $reviewer_ratings[] = [
                    'reviewer_id' => $reviewer_id,
                    'name' => User::find($reviewer_id)->name,
                    'average_rating' => $average_rating
                ];
            }

            // Retrieve top 5 reviewers using sort by descending order
            $topreviewers= $reviewer_ratings->sortByDesc("average_rating")->take(5);

            return view("courses.topreviewers")->with("user", $user)->with("course", $course)->with("topreviewers",$topreviewers);

        }else{

            // If user is not authenticated
            return view("register_login");
        }
    }
}
