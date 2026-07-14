<?php

namespace App\Http\Requests;

use App\Models\Allocation;
use App\Models\Claim;
use App\Rules\InstitutionAllocationReached;
use App\Rules\ValidSin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ApplicationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {

        // Check if the authenticated user has the necessary permissions to edit the institution.
        // You can access the authenticated user using the Auth facade or $this->user() method.
        return $this->user()->can('create', Claim::class);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'institution_guid.*' => 'The Institution field is required.',
            'program_guid.*' => 'The Program field is required.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $allocation = Allocation::where('guid', $this->input('allocation_guid'))->with('institution')->first();

        $rules = [
            'guid' => 'required',
            'claim_status' => 'required|string',
            'institution_guid' => 'required|exists:institutions,guid',
            'allocation_guid' => 'required|exists:allocations,guid',
            'program_guid' => 'required|exists:programs,guid',
            'user_guid' => 'required|exists:users,guid',
            'sin' => ['required', new ValidSin],
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'dob' => 'required|date_format:Y-m-d',
            'email' => 'required|email',
            'telephone' => 'nullable|string',
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => 'required|string',
            'province' => 'nullable|string',
            'country' => 'nullable|string',
            'zip_code' => 'required|string|regex:/^[A-Za-z]\d[A-Za-z]\d[A-Za-z]\d$/',
            'gender_identity' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'number_of_dependants' => 'nullable|integer',
            'disability_status' => 'nullable|string',
            'indigenous_identity' => 'nullable|string',
            'immigration_status' => 'nullable|string',
            'immigration_year' => 'nullable|integer',
            'visible_minority_status' => 'nullable|string',
            'highest_education_level' => 'nullable|string',
            'official_language_choice' => 'nullable|string',
            'official_language_service' => 'nullable|string',
            'employment_status_intake' => 'nullable|string',
            'employment_status_exit' => 'nullable|string',
            'precarious_employment' => 'nullable|string',
            'intervention_name' => 'nullable|string',
            'intervention_code' => 'nullable|string',
            'intervention_start_date' => 'nullable|date_format:Y-m-d',
            'intervention_end_date' => 'nullable|date_format:Y-m-d',
            'intervention_outcome' => 'nullable|string',
            'credential_earned' => 'nullable|string',
            'noc_code' => 'nullable|string',
            'naics_code' => 'nullable|string',
            'action_plan_start_date' => 'nullable|date_format:Y-m-d',
            'action_plan_end_date' => 'nullable|date_format:Y-m-d',
            'action_plan_outcome' => 'nullable|string',
            'action_plan_outcome_date' => 'nullable|date_format:Y-m-d',
            'literacy_essential_skills_increase' => 'nullable|string',
            'expiry_date' => 'required|date_format:Y-m-d',
            'correction_amount' => 'nullable|numeric',
            'correction_comment' => 'required_if:correction_amount,!null',

        ];

        if ($this->claim_status === 'Draft') {
            $rules = array_merge($rules, [
                'agreement_confirmed' => 'boolean',
                'registration_confirmed' => 'boolean',
            ]);

        } elseif ($this->claim_status === 'Submitted') {
            $rules = array_merge($rules, [

                'allocation_limit_reached' => new InstitutionAllocationReached($allocation),
                'agreement_confirmed' => 'required|boolean|accepted:true',
                'registration_confirmed' => 'required|boolean|accepted:true',

                'registration_fee' => 'nullable|numeric',
                'materials_fee' => 'nullable|numeric',
                'program_fee' => 'nullable|numeric',
                'estimated_hold_amount' => 'required|numeric',
                'total_claim_amount' => 'nullable|numeric',
                'claim_percent' => 'required|numeric',

            ]);
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // If the status is "Cancelled", do not modify any other fields
        if ($this->claim_status === 'Cancelled' || $this->claim_status === 'Expired') {
            return;
        }

        $allocation = Allocation::where('institution_guid', $this->institution_guid)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->first();

        if (! $allocation) {
            throw new \Exception('Allocation not found.');
        }

        // The applicant's profile is captured on the claim itself from the
        // submitted form. Only the owning user and allocation are derived here.
        $this->merge([
            'allocation_limit_reached' => null,
            'guid' => Str::orderedUuid()->getHex(),
            'last_touch_by_user_guid' => $this->user()->guid,

            'user_guid' => $this->user()->guid,
            'allocation_guid' => $allocation->guid,

            'agreement_confirmed' => $this->toBoolean($this->agreement_confirmed),
            'registration_confirmed' => $this->toBoolean($this->registration_confirmed),

            'registration_fee' => 0,
            'materials_fee' => 0,
            'program_fee' => 0,
            'estimated_hold_amount' => 0,
            'total_claim_amount' => 0,
            'claim_percent' => 0,
            'expiry_date' => $allocation->py->end_date,
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
