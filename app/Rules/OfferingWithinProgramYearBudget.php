<?php

namespace App\Rules;

use App\Models\ProgramOffering;
use App\Models\ProgramYear;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OfferingWithinProgramYearBudget implements ValidationRule
{
    protected ?string $programYearGuid;

    protected ?string $excludeOfferingGuid;

    public function __construct(?string $programYearGuid, ?string $excludeOfferingGuid = null)
    {
        $this->programYearGuid = $programYearGuid;
        $this->excludeOfferingGuid = $excludeOfferingGuid;
    }

    /**
     * Validate that an offering's total amount fits within the budget of the
     * program year it is associated with:
     *   1. The offering total amount cannot exceed the program year budget.
     *   2. The offering total amount plus the total amount of every other
     *      offering in that program year cannot exceed the program year budget.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $totalAmount = (float) $value;

        $programYear = ProgramYear::where('guid', $this->programYearGuid)->first();

        // A missing program year is handled by the `exists` rule on the field.
        if (! $programYear) {
            return;
        }

        $budget = (float) $programYear->total_budget;

        // Rule 1: the offering on its own cannot exceed the program year budget.
        if ($totalAmount > $budget) {
            $fail(sprintf(
                'The offering total amount ($%s) cannot exceed the program year total budget of $%s.',
                number_format($totalAmount, 2),
                number_format($budget, 2)
            ));

            return;
        }

        // Rule 2: the offering plus the amount already committed to the other
        // offerings in the program year cannot exceed the program year budget.
        $committed = (float) ProgramOffering::where('program_year_guid', $this->programYearGuid)
            ->when($this->excludeOfferingGuid, function ($query) {
                $query->where('guid', '!=', $this->excludeOfferingGuid);
            })
            ->sum('total_amount');

        if (($totalAmount + $committed) > $budget) {
            $fail(sprintf(
                'The offering total amount ($%s) plus the other offerings in this program year ($%s) exceeds the program year total budget of $%s.',
                number_format($totalAmount, 2),
                number_format($committed, 2),
                number_format($budget, 2)
            ));
        }
    }
}
