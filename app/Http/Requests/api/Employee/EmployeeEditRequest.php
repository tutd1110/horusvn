<?php

namespace App\Http\Requests\api\Employee;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\User;

/**
 * Employee Edit Request
*/
class EmployeeEditRequest extends ApiBaseRequest
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
        $rules += array('id' => ['required', 'integer', 'exists:users,id',]);

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

        $rules += array('avatar' => [
            'bail',
            'nullable',
            'file',
            $validate_file_extensions,
            $validate_avatar_same_name
        ]);

        $rules += array('fullname' => ['required']);
        $rules += array('phone' => ['required']);
        $rules += array('birthday' => ['bail', 'required', 'date']);
        $rules += array('email' => ['required', 'email:rfc,dns']);
        $rules += array('department_id' => ['required']);
        $rules += array('position' => ['required']);
        $rules += array('permission' => ['required']);
        $rules += array('check_type' => ['required', 'integer']);
        $rules += array('user_status' => ['required', 'integer']);
        $rules += array('date_official' => ['nullable', 'date']);
        $rules += array('created_at' => ['required', 'date']);

        $rules += array('password' => ['nullable', 'confirmed']);
        // $rules += array('password_confirmation' => ['nullable']);
        $rules += array('check_updated_at' => ['required', 'date',]);

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
        $msgs += array('id.required' => __('MSG-E-004'));
        $msgs += array('id.exists' => __('MSG-E-006'));
        $msgs += array('id.integer' => __('MSG-E-005'));


        // $msgs += array('avatar.required' => __('MSG-E-004'));
        $msgs += array('avatar.file' => __('MSG-E-004'));

        $msgs += array('fullname.required' => __('MSG-E-004'));

        $msgs += array('phone.required' => __('MSG-E-004'));

        $msgs += array('birthday.required' => __('MSG-E-004'));
        $msgs += array('birthday.date' => __('MSG-E-005'));

        $msgs += array('date_official.date' => __('MSG-E-005'));

        $msgs += array('email.required' => __('MSG-E-004'));
        $msgs += array('email.email' => __('MSG-E-005'));

        $msgs += array('department_id.required' => __('MSG-E-004'));
        $msgs += array('position.required' => __('MSG-E-004'));
        $msgs += array('permission.required' => __('MSG-E-004'));

        $msgs += array('check_type.required' => __('MSG-E-004'));
        $msgs += array('check_type.integer' => __('MSG-E-005'));

        $msgs += array('user_status.required' => __('MSG-E-004'));
        $msgs += array('user_status.integer' => __('MSG-E-005'));

        $msgs += array('created_at.required' => __('MSG-E-004'));
        $msgs += array('created_at.date' => __('MSG-E-005'));

        $msgs += array('password.confirmed' => __('MSG-E-007'));

        $msgs += array('check_updated_at.required' => __('MSG-E-004'));
        $msgs += array('check_updated_at.date' => __('MSG-E-005'));

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
            'id' => 'Mã nhân viên',
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
            'check_type' => 'Loại chấm công',
            'user_status' => 'Trạng thái hoạt động',
            'type' => 'Loại nhân viên',
            'password' => 'Mật khẩu',
            'date_official' => 'Ngày bắt đầu làm việc chính thức',
            'created_at' => 'Ngày bắt đầu làm việc',
            'check_updated_at' => 'Ngày giờ sửa đổi'
        ];
    }
}
