<?php

namespace App\Http\Requests\api\Statistial;

use Illuminate\Foundation\Http\FormRequest;

class ExportStatistialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
//            'type'=>'required|string|in:all,violations,total_effort_hour,go_early_total,leave_late_total,rate_late,total_day_work,rate_dayoff,warrior_year',
//            'start_date'=>'required|date',
//            'end_date'=>'required|date',
        ];
    }
}
