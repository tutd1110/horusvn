<?php

namespace App\Http\Requests\api\Statistial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StatistialTopRequest extends FormRequest
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
    public function rules(Request $request)
    {
       return [
           'start_date'=>'required|date',
           'end_date'=>'required|date',
           'sort_options'=>'array|required',
           'sort_options.go_early_total'=>'required|array',
           'sort_options.go_early_total.sort_type'=>'required|in:topFiveHighest,topFiveLowest',
           'sort_options.go_early_total.sort_quantity'=>'required|in:5,10,15',
           'sort_options.leave_late_total'=>'required|array',
           'sort_options.leave_late_total.sort_type'=>'required|in:topFiveHighest,topFiveLowest',
           'sort_options.leave_late_total.sort_quantity'=>'required|in:5,10,15',
           'sort_options.total_effort_hour'=>'required|array',
           'sort_options.total_effort_hour.sort_type'=>'required|in:topFiveHighest,topFiveLowest',
           'sort_options.total_effort_hour.sort_quantity'=>'required|in:5,10,15',
           'sort_options.violations'=>'required|array',
           'sort_options.violations.sort_type'=>'required|in:topFiveHighest,topFiveLowest',
           'sort_options.violations.sort_quantity'=>'required|in:5,10,15',
           'sort_options.rate_late'=>'required|array',
           'sort_options.rate_late.sort_type'=>'required|in:topFiveHighest,topFiveLowest',
           'sort_options.rate_late.sort_quantity'=>'required|in:5,10,15',
           'sort_options.total_day_work'=>'required|array',
           'sort_options.total_day_work.sort_type'=>'required|in:topFiveHighest,topFiveLowest',
           'sort_options.total_day_work.sort_quantity'=>'required|in:5,10,15',
           'sort_options.rate_dayoff'=>'required|array',
           'sort_options.rate_dayoff.sort_type'=>'required|in:topFiveHighest,topFiveLowest',
           'sort_options.rate_dayoff.sort_quantity'=>'required|in:5,10,15',
       ];
    }
}
