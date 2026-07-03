<?php

namespace App\Http\Requests\Operational;

use App\Models\CompletionRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalCompletionRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && (
                $user->can('complete.approve')
                || $user->can('complete.reject')
                || $user->can('complete.request_revision')
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'approval_status' => ['required', Rule::in(CompletionRecord::statuses())],
            'revision_note' => ['nullable', 'required_if:approval_status,'.CompletionRecord::STATUS_REVISI, 'string', 'max:1000'],
            'updated_at' => ['required', 'date'],
        ];
    }
}
