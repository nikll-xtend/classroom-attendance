<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Rules\StudentBelongsToClassroom;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use App\Jobs\MarkAttendanceJob;
use App\Jobs\BulkMarkAttendanceJob;
use App\Services\Contracts\AbilityCheckerInterface;



class AttendanceController extends Controller
{
    protected $user;
    protected $abilityChecker;


    public function __construct(AbilityCheckerInterface $abilityChecker)
    {
        $this->abilityChecker = $abilityChecker;
        $this->user = Auth::user();
    }

    public function store(Request $request)
    {

        if (!$this->abilityChecker->can('mark-attendance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'classroom_id' => 'required|exists:classrooms,id',
            'student_id'   => ['required', 'exists:students,id', new StudentBelongsToClassroom($request->classroom_id)],
            'status'       => 'required|in:present,absent',
            'remarks'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }
        $data = $validator->validated();
        $data['marked_by'] = Auth::id();

        try {
            //  student belongs to the requested classroom check

            $studentClassroomId = Redis::get("student:{$request->student_id}:classroom");
            if (!$studentClassroomId) {
                $student = Student::find($request->student_id);
                if (!$student) {
                    return response()->json(['message' => 'Student not found'], 404);
                }
                Redis::set("student:{$student->id}:classroom", $student->classroom_id);
                $studentClassroomId = $student->classroom_id;
            }
            if ($studentClassroomId != $request->classroom_id) {
                return response()->json(['message' => 'Student does not belong to this classroom'], 400);
            }

            // Preventing multiple attendece  -> One attendance for a user per day
            $attendanceKey = "attendance:{$request->classroom_id}:{$request->student_id}:" . today()->toDateString();
            $attendanceMarked = Redis::get($attendanceKey);
            if ($attendanceMarked) {
                return response()->json(['message' => 'Attendance already marked for today'], 400);
            }

            Attendance::create([
                'classroom_id' => $data['classroom_id'],
                'student_id'   => $data['student_id'],
                'marked_by'    => $data['marked_by'],
                'status'       => $data['status'],
                'remarks'      => $data['remarks'],
                'marked_at'    => now(),
            ]);

            Redis::setex($attendanceKey, 86400, 'marked');
            return response()->json(['message' => 'Attendance marked successfully'], 201);
        } catch (\Exception $e) {
            Log::error('Failed to mark attendance: ' . $e->getMessage(), [
                'classroom_id' => $request->classroom_id,
                'student_id'   => $request->student_id,
                'status'       => $request->status,
                'remarks'      => $request->remarks,
                'exception'    => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Something went wrong', 'error' => 'Unable to mark attendance. Please try again later.'], 500);
        }
    }



    public function getAttendanceById($id)
    {
        if (!$this->abilityChecker->can('view-attendance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        try {
            $attendance = Redis::get('attendance:' . $id);

            if ($attendance) {
                $attendance = json_decode($attendance);
                Log::info("Attendance fetched from Redis for ID: $id");
                return response()->json(['data' => $attendance], 200);
            }

            $attendance = Attendance::select('id', 'status', 'remarks', 'marked_at', 'student_id', 'classroom_id', 'marked_by')
                ->with([
                    'student:id,first_name,last_name,email,phone_number',
                    'classroom:id,name',
                ])
                ->findOrFail($id);

            if (!$attendance) {
                return response()->json(['message' => 'Attendance not found'], 404);
            }

            Redis::setex('attendance:' . $id, 300, json_encode($attendance));
            return response()->json(['data' => $attendance], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching attendance: ' . $e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }



    public function getAttendanceForStudent($student_id)
    {
        if (!$this->abilityChecker->can('view-attendance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($this->user->role, ['teacher', 'admin'], true)) {
            return response()->json(['message' => 'Forbidden: Only teachers or admins allowed'], 403);
        }

        try {
            $attendance = Redis::get('attendance:student:' . $student_id);

            if ($attendance) {
                $attendance = json_decode($attendance);
                Log::info("Attendance fetched from Redis for student ID: $student_id");
                return response()->json($attendance);
            }

            $attendance = Attendance::select('id', 'status', 'remarks', 'marked_at', 'student_id', 'classroom_id', 'marked_by')
                ->with([
                    'student:id,first_name,last_name,email,phone_number',
                    'classroom:id,name',
                ])
                ->where('student_id', $student_id)
                ->get();


            if ($attendance->isEmpty()) {
                return response()->json(['message' => 'No attendance records found for this student'], 404);
            }

            Redis::setex('attendance:student:' . $student_id, 300, json_encode($attendance));
            Log::info("Attendance fetched from DB and cached in Redis for student ID: $student_id");

            return response()->json($attendance);
        } catch (\Exception $e) {
            Log::error('Error fetching attendance for student ID ' . $student_id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function updateAttendance(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:present,absent',
                'remarks' => 'nullable|string',
            ]);

            $attendance = Attendance::findOrFail($id);

            if (Auth::user()->cannot('update', $attendance)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $attendance->update([
                'status' => $request->status,
                'remarks' => $request->remarks,
            ]);

            $attendanceKey = 'attendance:' . $attendance->id;
            Redis::set($attendanceKey, json_encode($attendance));

            return response()->json(['message' => 'Attendance updated successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error updating attendance for ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }


    public function deleteAttendance(Request $request, $id)
    {
        try {
            if (!$this->abilityChecker->can('manage-attendance')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $attendance = Attendance::findOrFail($id);
            $attendance->delete();
            Log::info('Attendance deleted successfully', [
                'attendance_id' => $id,
                'deleted_by' => Auth::user()->id,
            ]);

            return response()->json(['message' => 'Attendance deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting attendance', [
                'attendance_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Something went wrong while deleting attendance'], 500);
        }
    }


    public function bulkMarkAttendance(Request $request)
    {
        if (!$this->abilityChecker->can('mark-attendance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $data = $request->validate([
                'attendance' => 'required|array',
                'attendance.*.student_id' => 'required|exists:students,id',
                'attendance.*.classroom_id' => 'required|exists:classrooms,id',
                'attendance.*.status' => 'required|in:present,absent',
                'attendance.*.marked_by' => 'required|exists:users,id',
                'attendance.*.remarks' => 'nullable|string',
            ]);

            BulkMarkAttendanceJob::dispatch($data['attendance']);

            return response()->json(['message' => 'Bulk attendance marked successfully'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            Log::error('Error marking bulk attendance: ' . $e->getMessage());
            return response()->json(['message' => 'Something went wrong while marking bulk attendance'], 500);
        }
    }
}
