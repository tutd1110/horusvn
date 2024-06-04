<?php

namespace App\Http\Requests\api\Task\TaskFile;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Task File Store Request
*/
class TaskFileStoreRequest extends ApiBaseRequest
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

        $rules += array('task_id' => ['required', 'integer']);

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

        $msgs += array('task_id.required' => __('MSG-E-004'));
        $msgs += array('task_id.integer' => __('MSG-E-005'));
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
            'task_id' => 'Mã công việc',
            'file' => 'Ảnh'
        ];
    }
}
