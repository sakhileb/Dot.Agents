<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used for both approve and reject actions on a skill approval.
 * reviewer_notes is optional for approvals but required for rejections.
 */
class ReviewSkillApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Gate::authorize('review', $approval) in controller
    }

    public function rules(): array
    {
        $isReject = str_ends_with($this->route()->getName() ?? '', 'reject');

        return [
            'reviewer_notes' => [
                $isReject ? 'required' : 'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
