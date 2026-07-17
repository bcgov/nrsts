<?php

namespace App\Http\Requests;

use App\Models\Claim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ApplicationTransitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The learner may only advance their own application through the
     * apprentice-training portion of the workflow.
     */
    public function authorize(): bool
    {
        $claim = Claim::find($this->id);

        if (! $claim) {
            return false;
        }

        if ($claim->user_guid !== $this->user()->guid) {
            Log::warning('ApplicationTransitionRequest authorization failed: claim not owned by user', [
                'claim_id' => $claim->id,
                'user_id' => $this->user()?->id,
            ]);

            return false;
        }

        return $this->user()->can('create', Claim::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'id' => 'required|exists:claims,id',
            'claim_status' => 'required|string|in:Training Started,Training Ended',
        ];

        if ($this->claim_status === 'Training Ended') {
            $rules['employment_status_exit'] = 'required|string';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'employment_status_exit.required' => 'Please select your Employment Status (Exit) before ending your training.',
        ];
    }

    /**
     * Enforce the allowed status transitions for the learner.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $claim = Claim::find($this->id);

            if (! $claim) {
                return;
            }

            if ($this->claim_status === 'Training Started') {
                if ($claim->claim_status !== 'EI Confirmed' || empty($claim->ei_reference_code)) {
                    $validator->errors()->add('claim_status', 'This application cannot start training yet.');
                }
            }

            if ($this->claim_status === 'Training Ended') {
                if ($claim->claim_status !== 'Training Started') {
                    $validator->errors()->add('claim_status', 'This application cannot be marked as Training Ended.');
                }
            }
        });
    }
}
