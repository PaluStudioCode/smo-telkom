<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(
        Request $request,
        string $module,
        string $action,
        ?Model $record = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'module' => $module,
            'action' => $action,
            'record_type' => $record ? $record::class : null,
            'record_id' => $record?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
