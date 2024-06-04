<?php

namespace App\Http\Requests\api\Post;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Post Register Request
*/
class PostRegisterRequest extends ApiBaseRequest
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

        $rules += array('title' => ['required', 'string']);
        $rules += array('content' => ['required', 'string']);
        $rules += array('status' => ['required', 'integer']);
        $rules += array('files' => ['nullable', 'array']);

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
        $rules += array('files.*' => ['nullable', 'file', $validate_file_extensions, $validate_file_size]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('title.string' => __('MSG-E-005'));
        $msgs += array('title.required' => __('MSG-E-004'));
        $msgs += array('content.string' => __('MSG-E-005'));
        $msgs += array('content.required' => __('MSG-E-004'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('status.required' => __('MSG-E-004'));
        $msgs += array('files.array' => __('MSG-E-005'));
        $msgs += array('files.*' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'title' => 'Tiêu đề',
            'content' => 'Nội dung',
            'status' => 'Trạng thái',
            'files' => 'File',
            'files.*' => 'File'
        ];
    }
}
