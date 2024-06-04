<?php

namespace App\Http\Requests\api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Base request for API
*/
class ApiBaseRequest extends FormRequest
{
    /**
     * [override] Handling when validation fails
     *
     * @param Validator $validator
     * @throw HttpResponseException
     * @see FormRequest::failedValidation()
     */
    protected function failedValidation(Validator $validator)
    {
        $response['status']  = Response::HTTP_UNPROCESSABLE_ENTITY;
        
        // Return a combined validation message.
        $ctr = 0;
        $errors = '';
        foreach ($validator->errors()->toArray() as $key => $valueList) {
            foreach ($valueList as $keySub => $valueSub) {
                if ($ctr > 0) {
                    $errors .= '<br>';
                }
                $errors .= $valueSub;
                $ctr++;
            }
        }
        $response['errors'] = $errors;
        $response['errors_list']  = $validator->errors()->toArray();

        throw new HttpResponseException(
            response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
