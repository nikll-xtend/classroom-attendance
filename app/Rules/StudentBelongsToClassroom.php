<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Student;


class StudentBelongsToClassroom implements ValidationRule
{
    protected $classroom_id;


    public function __construct($classroom_id)
    {
        $this->classroom_id = $classroom_id;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $student = Student::find($value);
        if ($student && $student->classroom_id != $this->classroom_id) {
            $fail('Student does not belong to this classroom');
        }
    }
}
