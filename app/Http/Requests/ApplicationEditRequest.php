<?php

namespace App\Http\Requests;

use App\Models\Claim;
use App\Models\ProgramOffering;
use App\Rules\OfferingSeatsReached;
use App\Rules\ValidSin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ApplicationEditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $claim = Claim::find($this->id);

        // Prevent updates if the current claim_status is not "Claimed" and the claim's
        // offering is not active. This is to prevent updates to claims that are not in an
        // active offering and are not in "Claimed" status. Drafts are exempt because they
        // do not have an offering assigned until the applicant selects one.
        if ($claim->claim_status !== 'Claimed'
            && $claim->claim_status !== 'Draft'
            && ! ($claim->offering && $claim->offering->offering_status === 'approved')) {
            Log::warning('ApplicationEditRequest authorization failed: Offering not active', [
                'claim_id' => $claim->id,
                'claim_status' => $claim->claim_status,
                'offering_active_status' => $claim->offering?->offering_status === 'approved',
                'user_id' => $this->user()?->id,
            ]);
            return false;
        }
        
        // Check if the authenticated user has the necessary permissions to edit the institution.
        // You can access the authenticated user using the Auth facade or $this->user() method.
        $canCreate = $this->user()->can('create', Claim::class);
        
        if (!$canCreate) {
            Log::warning('ApplicationEditRequest authorization failed: User lacks create permission', [
                'claim_id' => $claim->id,
                'user_id' => $this->user()?->id,
            ]);
        }
        
        return $canCreate;
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

        $rules = [
            'id' => 'required',
            'guid' => 'required',
            'claim_status' => 'required|string',
            'institution_guid' => 'required|exists:institutions,guid',
            'program_offering_guid' => 'required|exists:program_offerings,guid',
            'program_guid' => 'required|exists:programs,guid',
            'apprentice_number' => ($this->claim_status === 'Submitted' ? 'required' : 'nullable').'|string',
            'user_guid' => 'required|exists:users,guid',
            'social_insurance_number' => ['required', new ValidSin],
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'email_address' => 'required|email',
            'phone_number' => 'nullable|string',
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => 'required|string',
            'province' => 'nullable|string',
            'region' => $this->claim_status === 'Submitted'
                ? ['required', 'string', Rule::exists('utils', 'field_name')->where('field_type', 'Regions')->where('active_flag', true)]
                : ['nullable', 'string'],
            'country' => 'nullable|string',
            'postal_code' => 'required|string|regex:/^[A-Za-z]\d[A-Za-z]\d[A-Za-z]\d$/',
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

        $claim = Claim::find($this->id);
        $offering = ProgramOffering::where('institution_guid', $this->institution_guid)
            ->where('program_guid', $this->program_guid)
            ->where('offering_status', 'approved')
            ->orderByDesc('created_at')
            ->first();

        if (! $offering) {
            throw new \Exception('Active program offering not found.');
        }

        // Preserve the owning user on the claim; profile fields come from the form.
        $this->merge([
            'allocation_limit_reached' => null,
            'last_touch_by_user_guid' => $this->user()->guid,

            'user_guid' => $claim?->user_guid ?? $this->user()->guid,
            'program_offering_guid' => $offering->guid,
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

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Editing this application is not authorized. Make sure you are submitting an application for the current active year.'
        );
    }
}
