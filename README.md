# Laravel_Project
 Student_Teacher Assessment Web Application
A web application for students to share and submit their peer reviews for a course. Teachers can manage courses and mark the assessments.

Terminology Review submitted: review submitted by the reviewer, written for their peer/reviewee. Review received: review received by the reviewee, written by their reviewer.

Details The implementation includes Laravel’s migrations, seeders, models, ORM/Eloquent, route, controllers, validator, view and templating.

There are two types of users of this system: Teachers and Students.

Students need to register before they can login. Students need to supply their name, email, and s-number when they register. Teachers are simply seeded into the database. For testing purpose, there are sufficient seeded teachers and students.

All users need to login before they can access the functionalities of this system. Users login with their s-number and password. Once a user logs in, their name and user type (teacher or student) will be displayed at the top of every page.

A logged in user should be able to log out.

Home page: once logged in, the user can see a list of courses they are enrolled in or teaching. As per real-world, a student can enrol into multiple courses, and a teacher can teach multiple courses. A course has a course code and name. Clicking on a course brings the user to course details page.

Teacher can manually enrol a registered student into a course.

Details page for a course displays the teachers and the list of (peer review) assessments for this course (e.g. Week 1 Assessment). Next to each assessment, it should display its due date. User can click on an assessment to bring them to the details page for that assessment (see below).

From the course details page, a teacher can add a peer review assessment to this course. A peer review assessment should contain an assessment title (up to 20 characters), instruction (free text), the number of reviews required to be submitted (a number 1 or above), maximum score (between 1 and 100 inclusive), a due date and time, and a type. There are two types of peer review: student-select and teacher-assign (reviewee).

Teachers are allowed to update a peer review assessment, unless there has already been a submission. When updating, the old value should be shown.

The details page for an assessment for student has the following functionalities: a. Allows students to submit their peer review. This page displays the assessment title, instruction, number of required reviews, submitted review, and due date. For peer reviews that are of “student-select” type, students select their reviewees (from a drop-down menu of all students in this course) and enter their review text (free text with at least 5 characters). Students need to be able to submit the required number of reviews for this assessment. The system needs to ensure each review submitted must be for a different reviewee.

b. Displays the peer reviews received by this student for this assessment, including the name of the reviewer.

The details page for an assessment for teacher can only be accessed by teachers of this course for marking. This page will list all students in the course, and for each student it shows how many reviews this student has submitted and received, and the score for this assessment. Clicking on a student will show a page containing all the reviews submitted and received by this student. Teacher can then assign a score to this student for this assessment.

There is pagination in the marking page that lists all students. A page is limited to 10 students. There is sufficient data to show the pagination works.

A teacher can upload a text file containing a course information, which includes the course name, teachers, assessments, and the student enrolled in this course. Uploading this file will result in a new course created in the system with the supplied assessment, teachers, and students/enrolments.The file format used is json and data is provided in this file to achieve this requirement. The system should check that the course, with the same course code, does not already exist. There is a file prepared to show this feature works.

A feature to encourage reviewers to submit useful reviews for their reviewee without requiring reviews be marked. A solution provided is reviewees to rate the reviewer (say out of 5), then there is a page all users can access that lists the 5 reviewers with the highest average rating.

There are two types of peer review, “student-select” as described in 9a, and “teacher-assign”. This requirement is for teacher-assign. With Teacher-assign the teacher (randomly) assigns students to peer review group for each peer review. Students only review other students in their assigned group.
