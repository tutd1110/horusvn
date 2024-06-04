<?php

namespace App\Http\Requests\api\Employee;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Validation\Rules\File;
use App\Models\User;

/**
 * Employee Register Request
*/
class EmployeeRegisterRequest extends ApiBaseRequest
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

        /**
        *   $attribute
        *   $value
        *   $fail
        */
        $validate_file_extensions = function ($attribute, $value, $fail) {
            $fileExtensions = config('const.image_extension_list_base');

            $imageExtension = $value->getClientOriginalExtension();
            if (!str_contains($fileExtensions, $imageExtension) || !$imageExtension) {
                $fail(__('MSG-E-005', ['attribute' => 'Ảnh đại diện']));
            }
        };

        /**
        *   $attribute
        *   $value
        *   $fail
        */
        $validate_avatar_same_name = function ($attribute, $value, $fail) {
            //Same avatar name check
            $upAvatarNameWithExtension = $value->getClientOriginalName();
            $avatarName = User::where('avatar', $upAvatarNameWithExtension)->first();
            if ($avatarName) {
                $fail(__('MSG-E-008', ['attribute' => 'Ảnh đại diện']));
            }
        };

        /**
        *   $attribute
        *   $value
        *   $fail
        */
        // $validate_avatar_size = function ($attribute, $value, $fail) {
        //     //max size can be upload
        //     $fileSizeMax = config('const.avatar_size_max');

        //     //convert file size to MB
        //     $avatarSize = number_format($value->getSize()/1048576, 2);
        //     if ((float)$avatarSize > $fileSizeMax) {
        //         $fail(__('MSG-E-011', ['val' => $fileSizeMax]));
        //     }
        // };

        $rules += array('avatar' => [
            'bail',
            'required',
            'file',
            $validate_file_extensions,
            $validate_avatar_same_name,
            // $validate_avatar_size
        ]);

        $rules += array('fullname' => ['required']);
        $rules += array('phone' => ['required']);
        $rules += array('birthday' => ['required', 'date']);
        $rules += array('email' => ['required']);
        $rules += array('department_id' => ['required']);
        $rules += array('position' => ['required']);
        $rules += array('permission' => ['required']);
        $rules += array('password' => ['required']);
        $rules += array('created_at' => ['required', 'date']);
        $rules += array('avatar_width' => ['nullable', 'integer']);
        $rules += array('avatar_height' => ['nullable', 'integer', 'max:736']);
        $rules += array('avatar_left' => ['nullable', 'integer']);
        $rules += array('avatar_top' => ['nullable', 'integer']);
        $rules += array('type' => ['required', 'integer']);
        

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('avatar.required' => __('MSG-E-004'));
        $msgs += array('avatar.file' => __('MSG-E-004'));
        $msgs += array('fullname.required' => __('MSG-E-004'));
        $msgs += array('phone.required' => __('MSG-E-004'));
        $msgs += array('birthday.required' => __('MSG-E-004'));
        $msgs += array('birthday.date' => __('MSG-E-005'));
        $msgs += array('email.required' => __('MSG-E-004'));
        $msgs += array('department_id.required' => __('MSG-E-004'));
        $msgs += array('position.required' => __('MSG-E-004'));
        $msgs += array('permission.required' => __('MSG-E-004'));
        $msgs += array('password.required' => __('MSG-E-004'));
        $msgs += array('created_at.required' => __('MSG-E-004'));
        $msgs += array('created_at.date' => __('MSG-E-005'));
        $msgs += array('avatar_width.integer' => __('MSG-E-005'));
        $msgs += array('avatar_height.integer' => __('MSG-E-005'));
        $msgs += array('avatar_height.max' => __('MSG-E-005'));
        $msgs += array('avatar_left.integer' => __('MSG-E-005'));
        $msgs += array('avatar_top.integer' => __('MSG-E-005'));
        $msgs += array('type.required' => __('MSG-E-004'));
        $msgs += array('type.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'avatar' => 'Ảnh đại diện',
            'avatar_width' => 'Chiều rộng ảnh đại diện',
            'avatar_height' => 'Độ cao ảnh đại diện',
            'avatar_left' => 'Độ căn trái ảnh đại diện',
            'avatar_top' => 'Độ căn trên ảnh đại diện',
            'fullname' => 'Họ và tên',
            'phone' => 'Số điện thoại',
            'birthday' => 'Ngày sinh',
            'email' => 'Email',
            'department_id' => 'Bộ phận',
            'position' => 'Chức danh',
            'permission' => 'Quyền truy cập',
            'password' => 'Mật khẩu',
            'type' => 'Loại nhân viên',
            'created_at' => 'Ngày bắt đầu làm việc',
        ];
    }
}
