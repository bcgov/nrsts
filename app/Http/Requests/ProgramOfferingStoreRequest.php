<?php

namespace App\Http\Requests;

use App\Models\Program;
use App\Rules\OfferingWithinProgramYearBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProgramOfferingStoreRequest extends FormRequest
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
            'program_guid' => 'required|exists:programs,guid',
            'program_year_guid' => 'required|exists:program_years,guid',
            'institution_guid' => 'required|exists:institutions,guid',
            'offering_name' => 'required',
            'offering_description' => 'nullable',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location_name' => 'nullable',
            'total_amount' => ['required', 'numeric', 'min:0', new OfferingWithinProgramYearBudget($this->program_year_guid)],
            'total_seats' => 'required|integer|min:0',
            'active_status' => 'required|boolean',
            'created_by_guid' => 'required|exists:users,guid',
            'updated_by_guid' => 'required|exists:users,guid',
        ];
    }

    public function messages(): array
    {
        return [
            'program_guid.required' => 'Program is required',
            'institution_guid.required' => 'Institution is required',
            'offering_name.required' => 'Offering name is required',
            'total_amount.required' => 'Total amount is required',
            'total_seats.required' => 'Total seats is required',
            'end_date.after_or_equal' => 'End date must be on or after the start date',
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
            'created_by_guid' => $this->user()->guid,
            'updated_by_guid' => $this->user()->guid,
        ]);
    }

    /**
     * Convert to boolean.
     *
     * @return bool
     */
    private function toBoolean($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
