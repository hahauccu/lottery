<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LotteryStepsRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'step_name' => 'required',
            'is_visible' => 'required',
            'prize_number' => 'required',
            'is_repeat_draw' => 'required',
            'lottery_code' => 'required',
            'order' => 'required',
            'participate_list' => 'required',
            'id' => 'integer'
        ];
    }
}
