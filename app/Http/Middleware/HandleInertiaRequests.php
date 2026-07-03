<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => match ($user->role) {
                        'super_admin' => 'Super Admin',
                        'admin_inputer' => 'Admin / Inputer',
                        'account_manager' => 'Account Manager',
                        default => 'Pengguna',
                    },
                    'phone' => $user->phone,
                    'bio' => $user->bio,
                    'profile_photo_path' => $user->profile_photo_path,
                    'profile_photo_url' => $user->profile_photo_path
                        ? Storage::url($user->profile_photo_path)
                        : null,
                    'is_active' => $user->is_active,
                ] : null,
                'permissions' => $user ? $this->permissions() : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function permissions(): array
    {
        $permissions = [
            'dashboard.view_all',
            'dashboard.view_related',
            'order_status.view',
            'order_status.create',
            'order_status.update',
            'order_status.delete',
            'order_edk.view',
            'order_edk.create',
            'order_edk.update',
            'order_edk.delete',
            'complete.view',
            'complete.create',
            'complete.update',
            'complete.delete',
            'complete.approve',
            'complete.reject',
            'complete.request_revision',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'profile.update_self',
            'password.update_self',
        ];

        return collect($permissions)
            ->mapWithKeys(fn (string $permission) => [$permission => Gate::allows($permission)])
            ->all();
    }
}
