<?php

namespace App\Http\Requests\Operational;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompletionRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var CompletionRecord|null $completionRecord */
        $completionRecord = $this->route('completion_record');

        if (! $user || ! $completionRecord || ! $user->can('complete.update')) {
            return false;
        }

        return $user->isSuperAdmin() || $completionRecord->inputer_id === $user->id;
    }

    protected function prepareForValidation(): void
    {
        /** @var CompletionRecord $completionRecord */
        $completionRecord = $this->route('completion_record');

        $approvalStatus = $this->user()->isAdminInputer()
            ? $completionRecord->approval_status
            : ($this->input('approval_status') ?: $completionRecord->approval_status);

        if ($this->user()->isAdminInputer() && $completionRecord->approval_status === CompletionRecord::STATUS_REVISI) {
            $approvalStatus = CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN;
        }

        $this->merge([
            'inputer_id' => $this->user()->isAdminInputer() ? $this->user()->id : $this->input('inputer_id'),
            'approval_status' => $approvalStatus,
            'revision_note' => $this->user()->isAdminInputer() ? $completionRecord->revision_note : $this->input('revision_note'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var CompletionRecord $completionRecord */
        $completionRecord = $this->route('completion_record');

        return [
            'completion_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('completion_records', 'completion_number')
                    ->where('period_month', $this->input('period_month'))
                    ->ignore($completionRecord),
            ],
            'order_status_id' => ['nullable', 'integer', Rule::exists('order_statuses', 'id')],
            'order_edk_id' => ['nullable', 'integer', Rule::exists('order_edks', 'id')],
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            'approval_status' => ['required', Rule::in(CompletionRecord::statuses())],
            'completed_at' => ['nullable', 'date'],
            'revision_note' => ['nullable', 'required_if:approval_status,'.CompletionRecord::STATUS_REVISI, 'string', 'max:1000'],
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'updated_at' => ['required', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->any()) {
                return;
            }

            $this->validateLinkedOrders($validator);
        });
    }

    private function validateLinkedOrders($validator): void
    {
        if (! $this->filled('order_status_id') && ! $this->filled('order_edk_id')) {
            $validator->errors()->add('order_status_id', 'Wajib memilih minimal salah satu Order Status atau Order EDK.');

            return;
        }

        $this->validateOrderStatusLink($validator);
        $this->validateOrderEdkLink($validator);
    }

    private function validateOrderStatusLink($validator): void
    {
        if (! $this->filled('order_status_id')) {
            return;
        }

        $orderStatus = OrderStatus::query()
            ->visibleTo($this->user())
            ->find($this->integer('order_status_id'));

        if (! $orderStatus) {
            $validator->errors()->add('order_status_id', 'Order Status tidak tersedia untuk pengguna ini.');

            return;
        }

        $this->validateRelationOwner($validator, 'order_status_id', $orderStatus->inputer_id, $orderStatus->account_manager_id);
    }

    private function validateOrderEdkLink($validator): void
    {
        if (! $this->filled('order_edk_id')) {
            return;
        }

        $orderEdk = OrderEdk::query()
            ->visibleTo($this->user())
            ->find($this->integer('order_edk_id'));

        if (! $orderEdk) {
            $validator->errors()->add('order_edk_id', 'Order EDK tidak tersedia untuk pengguna ini.');

            return;
        }

        $this->validateRelationOwner($validator, 'order_edk_id', $orderEdk->inputer_id, $orderEdk->account_manager_id);
    }

    private function validateRelationOwner($validator, string $field, int $inputerId, int $accountManagerId): void
    {
        if ($inputerId !== $this->integer('inputer_id') || $accountManagerId !== $this->integer('account_manager_id')) {
            $validator->errors()->add($field, 'Relasi order harus memiliki Inputer dan Account Manager yang sama.');
        }
    }
}
