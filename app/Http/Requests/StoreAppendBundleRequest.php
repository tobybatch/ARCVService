<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppendBundleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // TODO : determine of existing registration route protection is sufficient.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /*
         * These rules validate that the form data is well-formed.
         * It is NOT responsible for the context validation of that data.
         */
        $rules = [
            // MUST be present, not null and string
            'start' => 'required|string|exists:vouchers,code',
            // MAY be present, nullable, string, code exists, is GT start and same sponsor as start
            'end' => 'nullable|string|exists:vouchers,code|codeGreaterThan:start|sameSponsor:start',
        ];

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'start.required' => 'The voucher code is required.',
            'start.exists'  => 'The voucher code is invalid.',
            'end.exists'  => 'The last voucher code is invalid.',
        ];
    }
}
