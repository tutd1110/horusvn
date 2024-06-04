<?php

namespace App\Http\Requests\api\EmployeeReview;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Delete employee review base request class
*/
class ReviewDeleteRequest extends ApiBaseRequest
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
        $rules = [];
        $rules += array('review_id' => ['required', 'integer', 'exists:reviews,id',]);
        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('review_id.required' => __('MSG-E-004'));
        $msgs += array('review_id.exists' => __('MSG-E-006'));
        $msgs += array('review_id.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'review_id' => 'Mã đánh giá'
        ];
    }
}
