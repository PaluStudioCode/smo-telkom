<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait AssertsFreshModel
{
    protected function assertFresh(Model $model, ?string $updatedAt): void
    {
        if (! $updatedAt) {
            return;
        }

        $current = $model->updated_at?->format('Y-m-d H:i:s');
        $incoming = Carbon::parse($updatedAt)->format('Y-m-d H:i:s');

        if ($current !== $incoming) {
            throw ValidationException::withMessages([
                'updated_at' => 'Data sudah diperbarui oleh pengguna lain. Muat ulang halaman sebelum menyimpan.',
            ]);
        }
    }

    protected function updatedAtToken(Model $model): ?string
    {
        return $model->updated_at?->format('Y-m-d H:i:s');
    }
}
