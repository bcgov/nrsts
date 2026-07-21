<?php

namespace Modules\Institution\Http\Requests;

use App\Models\ProgramOffering;
use App\Rules\OfferingWithinProgramYearBudget;
use Illuminate\Foundation\Http\FormRequest;

class InstitutionOfferingEditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The offering must belong to the logged-in institution and still be a
     * draft. Once an offering is submitted, approved, declined or inactive the
     * institution can no longer change it.
     */
    public function authorize(): bool
    {
        $institution = $this->user()->institution;

        if (! $institution) {
            return false;
        }

        $offering = ProgramOffering::where('id', $this->id)->first();

        return $offering
            && $offering->institution_guid === $institution->guid
            && $offering->offering_status === 'draft';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:program_offerings,id',
            'guid' => 'required',
            'program_guid' => 'required|exists:programs,guid',
            'program_year_guid' => 'required|exists:program_years,guid',
            'institution_guid' => 'required|exists:institutions,guid',
            'offering_name' => 'required',
            'offering_description' => 'nullable',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location_name' => 'nullable',
            'total_amount' => ['required', 'numeric', 'min:0', new OfferingWithinProgramYearBudget($this->program_year_guid, $this->guid)],
            'total_seats' => 'required|integer|min:0',
            'offering_status' => 'required|in:draft,submitted',
            'updated_by_guid' => 'required|exists:users,guid',
        ];
    }

    public function messages(): array
    {
        return [
            'program_guid.required' => 'Program is required',
            'offering_name.required' => 'Offering name is required',
            'total_amount.required' => 'Total budget is required',
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
        $institution = $this->user()->institution;

        $this->merge([
            'institution_guid' => $institution?->guid,
            'offering_status' => $this->offering_status === 'submitted' ? 'submitted' : 'draft',
            'updated_by_guid' => $this->user()->guid,
        ]);
    }
}
