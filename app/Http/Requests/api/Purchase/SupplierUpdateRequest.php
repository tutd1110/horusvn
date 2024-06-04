<?php

namespace App\Http\Requests\api\Purchase;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Purchase;

/**
 * Store Task base request class
*/
class SupplierUpdateRequest extends ApiBaseRequest
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
        // $rules += array('name' => ['required', 'string']);
        $rules += array('tax_code' => ['string','required']);
        $rules += array('phone' => ['string','required']);
        $rules += array('address' => ['string','required']);
        $rules += array('price' => ['string','required']);
        $rules += array('delivery_time' => ['required']);
        $rules += array('file' => ['mimes:pdf','max:20480']);
        $rules += array('filePO' => ['mimes:pdf','max:20480']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('name.required' => __('MSG-E-004'));
        $msgs += array('tax_code.string' => __('MSG-E-005'));
        $msgs += array('tax_code.required' => __('MSG-E-004'));
        $msgs += array('phone.string' => __('MSG-E-005'));
        $msgs += array('phone.required' => __('MSG-E-004'));
        $msgs += array('address.string' => __('MSG-E-005'));
        $msgs += array('address.required' => __('MSG-E-004'));
        $msgs += array('price.string' => __('MSG-E-005'));
        $msgs += array('price.required' => __('MSG-E-004'));
        $msgs += array('delivery_time.required' => __('MSG-E-004'));
        $msgs += array('file.mimes' => __('MSG-E-004'));
        $msgs += array('filePO.mimes' => __('MSG-E-004'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'name' => 'Tên yêu cầu',
            'tax_code' => 'Mã số thuế',
            'phone' => 'Số điện thoại',
            'addres' => 'Địa chỉ',
            'price' => 'Giá trị đơn hàng',
            'delivery_time' => 'Thời gian giao',
            'file' => 'Báo giá',
            'filePO' => 'PO',
        ];

        return $attributes;
    }
}
