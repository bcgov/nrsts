<?php

namespace App\Http\Requests;

use App\Models\Claim;
use App\Rules\OfferingSeatsReached;
use App\Rules\ValidSin;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClaimEditRequest extends FormRequest
{
    /**
     * Allowed institution status transitions. Each key is the claim's current
     * status and the value lists the statuses it may move to. This enforces the
     * forward-only workflow: Submitted -> Hold -> Training Started -> (Completed |
     * Dropped Out), with Declined reachable while the claim is still Submitted or
     * on Hold. Any transition not listed here (including moving backward) is denied.
     */
    private const INSTITUTION_TRANSITIONS = [
        'Submitted' => ['Hold', 'Declined'],
        'Hold' => ['Training Started', 'Declined'],
        'Training Started' => ['Completed', 'Dropped Out'],
    ];

    /**
     * Statuses that require the institution to record a reason in outcome_status.
     */
    private const REASON_REQUIRED_STATUSES = ['Declined', 'Dropped Out'];

    /**
     * Terminal statuses that can no longer be modified by the institution.
     */
    private const TERMINAL_STATUSES = ['Completed', 'Declined', 'Dropped Out', 'Cancelled', 'Expired'];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $claim = Claim::find($this->id);

        if (! $claim) {
            Log::warning('ClaimEditRequest authorization failed: Claim not found', [
                'claim_id' => $this->id,
                'user_id' => $this->user()?->id,
            ]);
            return false;
        }

        $current = $claim->claim_status;
        $target = $this->claim_status;

        // Terminal claims can no longer be actioned by the institution.
        if (in_array($current, self::TERMINAL_STATUSES, true)) {
            Log::warning('ClaimEditRequest authorization failed: Claim in terminal status', [
                'claim_id' => $claim->id,
                'claim_status' => $current,
                'user_id' => $this->user()?->id,
            ]);
            return false;
        }

        // The claim's offering must be active for the institution to action it.
        if (! ($claim->offering && $claim->offering->offering_status === 'approved')) {
            Log::warning('ClaimEditRequest authorization failed: Offering not active', [
                'claim_id' => $claim->id,
                'claim_status' => $current,
                'offering_active_status' => $claim->offering?->offering_status === 'approved',
                'user_id' => $this->user()?->id,
            ]);
            return false;
        }

        // Only forward transitions defined in the workflow map are allowed. This
        // prevents the institution from moving the status backward or skipping steps.
        if (! is_null($target) && $target !== $current) {
            $allowed = self::INSTITUTION_TRANSITIONS[$current] ?? [];
            if (! in_array($target, $allowed, true)) {
                Log::warning('ClaimEditRequest authorization failed: Invalid status transition', [
                    'claim_id' => $claim->id,
                    'from' => $current,
                    'to' => $target,
                    'user_id' => $this->user()?->id,
                ]);
                return false;
            }
        }

        // The institution can never change the submitted program.
        if ($this->has('program_guid') && $this->input('program_guid') != $claim->program_guid) {
            Log::warning('ClaimEditRequest authorization failed: Cannot change the submitted program', [
                'claim_id' => $claim->id,
                'claim_status' => $current,
                'user_id' => $this->user()?->id,
            ]);
            return false;
        }

        // Check if the authenticated user has the necessary permissions to edit the claim.
        $canUpdate = $this->user()->can('update', $claim);

        if (! $canUpdate) {
            Log::warning('ClaimEditRequest authorization failed: User lacks update permission', [
                'claim_id' => $claim->id,
                'user_id' => $this->user()?->id,
            ]);
        }

        return $canUpdate;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'date_of_birth.*' => 'The Date of Birth field is invalid.',
            'outcome_status.required' => 'A reason is required in the outcome status for this action.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'id' => 'required',
            'guid' => 'required',
            'claim_status' => 'required|string',
            'program_guid' => 'nullable|exists:programs,guid',
            'outcome_status' => 'nullable|string',
            'ei_reference_code' => 'nullable|string',
        ];

        // Declined and Dropped Out require the institution to record a reason.
        if (in_array($this->claim_status, self::REASON_REQUIRED_STATUSES, true)) {
            $rules['outcome_status'] = 'required|string';
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
        if ($this->has('outcome_status') && is_string($this->outcome_status)) {
            $this->merge(['outcome_status' => trim($this->outcome_status)]);
        }
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
     * Sanitize and convert a value to float.
     *
     * @param  mixed  $value
     * @return float
     */
    protected function sanitizeAndConvertToFloat($value)
    {
        // Remove any non-numeric characters (except dot)
        $value = preg_replace('/[^0-9.]/', '', $value);

        // Convert to float
        return (float) $value;
    }
}
