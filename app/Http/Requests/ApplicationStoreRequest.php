<?php

namespace App\Http\Requests;

use App\Models\Claim;
use App\Models\ProgramOffering;
use App\Rules\OfferingSeatsReached;
use App\Rules\ValidSin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'apprentice_number.required' => 'The Apprentice Number is required to submit an application.',
            'region.required' => 'The Region is required to submit an application.',
            'region.exists' => 'The selected Region is invalid.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $offering = ProgramOffering::where('guid', $this->input('program_offering_guid'))->with('institution')->first();

        // Drafts may be saved incomplete; only a full submission requires every field.
        $isSubmission = $this->claim_status === 'Submitted';
        $req = $isSubmission ? 'required' : 'nullable';

        $rules = [
            'guid' => 'required',
            'claim_status' => 'required|string',
            'institution_guid' => $req.'|exists:institutions,guid',
            'program_offering_guid' => $req.'|exists:program_offerings,guid',
            'program_guid' => $req.'|exists:programs,guid',
            'apprentice_number' => $req.'|string',
            'user_guid' => 'required|exists:users,guid',
            'social_insurance_number' => $isSubmission ? ['required', new ValidSin] : ['nullable', new ValidSin],
            'first_name' => $req.'|string',
            'middle_name' => 'nullable|string',
            'last_name' => $req.'|string',
            'date_of_birth' => $req.'|date_format:Y-m-d',
            'email_address' => $req.'|email',
            'phone_number' => 'nullable|string',
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => $req.'|string',
            'province' => 'nullable|string',
            'region' => $isSubmission
                ? ['required', 'string', Rule::exists('utils', 'field_name')->where('field_type', 'Regions')->where('active_flag', true)]
                : ['nullable', 'string'],
            'country' => 'nullable|string',
            'postal_code' => $req.'|string|regex:/^[A-Za-z]\d[A-Za-z]\d[A-Za-z]\d$/',
            'gender' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'number_of_dependants' => 'nullable|integer',
            'disability_status' => 'nullable|boolean',
            'indigenous_status' => 'nullable|boolean',
            'indigenous_group' => 'nullable|string',
            'immigration_status' => 'nullable|string',
            'immigration_year' => 'nullable|integer',
            'visible_minority_status' => 'nullable|string',
            'racial_identity' => 'nullable|string',
            'is_visible_minority' => 'nullable|boolean',
            'highest_level_of_education' => 'nullable|string',
            'spoken_language' => 'nullable|string',
            'employment_status_intake' => 'nullable|string',
            'employment_status_exit' => 'nullable|string',
            'intervention_outcome' => 'nullable|string',

        ];

        if ($this->claim_status === 'Submitted') {
            $rules = array_merge($rules, [
                'allocation_limit_reached' => new OfferingSeatsReached($offering),
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

        // Normalize blank strings to null so "nullable" rules apply cleanly to drafts.
        $normalized = collect($this->all())->map(function ($value) {
            return is_string($value) && trim($value) === '' ? null : $value;
        })->all();
        $this->merge($normalized);

        // Derive the active offering only when both an institution and a program
        // have been chosen.
        $offering = null;
        if ($this->institution_guid && $this->program_guid) {
            $offering = ProgramOffering::where('institution_guid', $this->institution_guid)
                ->where('program_guid', $this->program_guid)
                ->where('offering_status', 'approved')
                ->orderByDesc('created_at')
                ->first();
        }

        if (! $offering && $this->claim_status === 'Submitted') {
            throw new \Exception('Active program offering not found.');
        }

        // The applicant's profile is captured on the claim itself from the
        // submitted form. Only the owning user and offering are derived here.
        $this->merge([
            'allocation_limit_reached' => null,
            'guid' => Str::orderedUuid()->getHex(),
            'last_touch_by_user_guid' => $this->user()->guid,

            'user_guid' => $this->user()->guid,
            'program_offering_guid' => $offering?->guid,
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
