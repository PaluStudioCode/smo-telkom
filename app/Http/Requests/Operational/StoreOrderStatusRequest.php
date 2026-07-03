<?php

namespace App\Http\Requests\Operational;

use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('order_status.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inputer_id' => $this->user()->isAdminInputer() ? $this->user()->id : $this->input('inputer_id'),
            'source_system' => $this->input('source_system') ?: 'Dashboard NCX',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('order_statuses', 'order_number')
                    ->where('period_month', $this->input('period_month')),
            ],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'service_name' => ['nullable', 'string', 'max:150'],
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            'status' => ['required', Rule::in(OrderStatus::statuses())],
            'provisioning_stage' => ['nullable', 'string', 'max:150'],
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'source_system' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
