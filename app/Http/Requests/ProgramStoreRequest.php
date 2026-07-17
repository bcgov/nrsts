<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProgramStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Program::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'guid' => 'required',
            'institution_guid' => 'required|exists:institutions,guid',
            'program_name' => 'required',
            'category' => 'nullable',
            'number_weeks' => 'required|integer|min:1',
            'number_levels' => 'required|integer|min:0',
            'funding_type' => 'required|exists:utils,field_name',

            'active_status' => 'required|boolean',

            'last_touch_by_user_guid' => 'required|exists:users,guid',

        ];
    }

    public function messages(): array
    {
        return [
            'institution_guid.required' => 'Institution is required',
            'program_name.required' => 'Program name is required',
            'number_weeks.required' => 'Number of weeks is required',
            'number_weeks.integer' => 'Number of weeks must be a whole number',
            'number_weeks.min' => 'Number of weeks must be greater than 0',
            'number_levels.required' => 'Number of levels is required',
            'number_levels.integer' => 'Number of levels must be a whole number',
            'number_levels.min' => 'Number of levels cannot be negative',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'guid' => Str::orderedUuid()->getHex(),

            'active_status' => $this->toBoolean($this->active_status),

            'last_touch_by_user_guid' => $this->user()->guid,
        ]);
    }

    /**
     * Convert to boolean
     *
     * @return bool
     */
    private function toBoolean($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
