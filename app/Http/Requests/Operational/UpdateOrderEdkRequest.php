<?php

namespace App\Http\Requests\Operational;

use App\Models\OrderEdk;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderEdkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var OrderEdk|null $orderEdk */
        $orderEdk = $this->route('order_edk');

        if (! $user || ! $orderEdk || ! $user->can('order_edk.update')) {
            return false;
        }

        return $user->isSuperAdmin() || $orderEdk->inputer_id === $user->id;
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
        /** @var OrderEdk $orderEdk */
        $orderEdk = $this->route('order_edk');

        return [
            'edk_reference' => [
                'required',
                'string',
                'max:100',
                Rule::unique('order_edks', 'edk_reference')
                    ->where('period_month', $this->input('period_month'))
                    ->ignore($orderEdk),
            ],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            'status' => ['required', Rule::in(OrderEdk::statuses())],
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'source_system' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'updated_at' => ['required', 'date'],
        ];
    }
}
