<?php

namespace App\Models\Concerns;

use App\Models\AccAuditLog;

/**
 * Records create/update/delete of accounting documents to acc_audit_logs.
 * Logging is best-effort: any failure (e.g. table not yet migrated) is swallowed
 * so it can never break the underlying accounting operation.
 */
trait LogsAccountingActivity
{
    public static function bootLogsAccountingActivity(): void
    {
        static::created(fn($model) => $model->writeAuditLog('created'));
        static::updated(fn($model) => $model->writeAuditLog('updated'));
        static::deleted(fn($model) => $model->writeAuditLog('deleted'));
    }

    public function writeAuditLog(string $action): void
    {
        try {
            $changes = null;
            if ($action === 'updated') {
                $changes = [];
                foreach ($this->getChanges() as $key => $new) {
                    if (in_array($key, ['updated_at', 'created_at'])) continue;
                    $changes[$key] = ['from' => $this->getOriginal($key), 'to' => $new];
                }
                if (empty($changes)) return; // only timestamps touched
            }

            AccAuditLog::create([
                'user_id'    => auth()->id(),
                'user_name'  => optional(auth()->user())->name,
                'action'     => $action,
                'model_type' => class_basename($this),
                'model_id'   => $this->getKey(),
                'label'      => $this->auditLabel(),
                'changes'    => $changes ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // best-effort: never break the main operation
        }
    }

    protected function auditLabel(): ?string
    {
        foreach (['entry_number', 'invoice_number', 'bill_number', 'voucher_number', 'name', 'code'] as $field) {
            if (!empty($this->{$field})) return (string) $this->{$field};
        }
        return null;
    }
}
