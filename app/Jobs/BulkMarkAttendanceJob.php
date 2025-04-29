<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use Illuminate\Support\Facades\Redis;

class BulkMarkAttendanceJob implements ShouldQueue
{
    use Queueable;

    protected $attendanceData;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(array $attendanceData)
    {
        $this->attendanceData = $attendanceData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();
            foreach ($this->attendanceData as $data) {

                $attendanceKey = "attendance:{$data['classroom_id']}:{$data['student_id']}:" . today()->toDateString();

                if (Redis::get($attendanceKey)) {
                    Log::info("Skipping attendance for student ID {$data['student_id']} in classroom ID {$data['classroom_id']} as it is already marked for today.");
                    continue; 
                }

               
                Attendance::create([
                    'classroom_id' => $data['classroom_id'],
                    'student_id'   => $data['student_id'],
                    'marked_by'    => $data['marked_by'],
                    'status'       => $data['status'],
                    'remarks'      => $data['remarks'] ?? null,
                    'marked_at'    => now(),
                ]);
                Redis::setex($attendanceKey, 86400, 'marked');
            }
            DB::commit();
            Log::info('Bulk attendance marked successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark bulk attendance: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Exception $exception): void
    {
        Log::error('BulkMarkAttendanceJob failed after maximum retries.', [
            'error' => $exception->getMessage(),
            'attendanceData' => $this->attendanceData,
        ]);
    }
}
