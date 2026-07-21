<?php

namespace Modules\Institution\Http\Requests;

use App\Models\ProgramYear;
use App\Models\Util;
use App\Rules\OfferingWithinProgramYearBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class InstitutionOfferingStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->institution !== null;
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
            'offering_status' => 'required|in:draft,submitted',
            'created_by_guid' => 'required|exists:users,guid',
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
     * The institution and program year are always derived from the logged-in
     * institution and the active program year, never trusted from the client.
     * Status is limited to draft or submitted.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $institution = $this->user()->institution;
        $activeProgramYear = ProgramYear::where('status', 'active')->first();

        // The offering budget is derived, not entered by the institution:
        // total seats x the Ministry's weekly support payment amount.
        $supportPaymentPerWeek = (float) (Util::where('field_type', 'Support Payment Per Week')
            ->where('active_flag', true)
            ->value('field_name') ?? 0);

        $this->merge([
            'guid' => Str::orderedUuid()->getHex(),
            'institution_guid' => $institution?->guid,
            'program_year_guid' => $activeProgramYear?->guid,
            'total_amount' => (int) $this->total_seats * $supportPaymentPerWeek,
            'offering_status' => $this->offering_status === 'submitted' ? 'submitted' : 'draft',
            'created_by_guid' => $this->user()->guid,
            'updated_by_guid' => $this->user()->guid,
        ]);
    }
}
