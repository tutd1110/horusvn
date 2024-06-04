<?php

namespace App\Http\Requests\api\EmployeeReview;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Review File Store Request
*/
class ReviewFileStoreRequest extends ApiBaseRequest
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
        $rules += array('employee_answer_id' => ['required', 'integer', 'exists:employee_answers,id',]);

        /**
        *   $attribute
        *   $value
        *   $fail
        */
        $validate_file_extensions = function ($attribute, $value, $fail) {
            $fileExtensions = config('const.image_extension_list_base');

            $imageExtension = $value->getClientOriginalExtension();
            if (!str_contains($fileExtensions, $imageExtension) || !$imageExtension) {
                $fail(__('MSG-E-005', ['attribute' => 'File']));
            }
        };
        /**
        *   $attribute
        *   $value
        *   $fail
        */
        $validate_file_size = function ($attribute, $value, $fail) {
            //max size can be upload
            $fileSizeMax = config('const.avatar_size_max');

            //convert file size to MB
            $avatarSize = number_format($value->getSize()/1048576, 2);
            if ((float)$avatarSize > $fileSizeMax) {
                $fail(__('MSG-E-011', ['val' => $fileSizeMax]));
            }
        };
        $rules += array('file' => ['required', 'file', $validate_file_extensions, $validate_file_size]);

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
        $msgs += array('employee_answer_id.required' => __('MSG-E-004'));
        $msgs += array('employee_answer_id.exists' => __('MSG-E-006'));
        $msgs += array('employee_answer_id.integer' => __('MSG-E-005'));
        $msgs += array('file.file' => __('MSG-E-005'));
        $msgs += array('file.required' => __('MSG-E-004'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'review_id' => 'Mã đánh giá',
            'employee_answer_id' => 'Mã câu trả lời',
            'file' => 'Ảnh'
        ];
    }
}
