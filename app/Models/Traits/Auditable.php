<?php

namespace App\Models\Traits;



use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::creating(function (Model $model) {
            if (auth()->check()) {
                $model->is_active  = true;
                $model->created_by = static::getUserSnapshot(auth()->user());
                $model->updated_at = null;
            }
        });

        static::updating(function (Model $model) {
            if (auth()->check()) {
                $model->updated_by = static::getUserSnapshot(auth()->user());
            }
        });

        static::deleting(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                if (auth()->check()) {
                    $model->is_active = false;
                    $model->deleted_by = static::getUserSnapshot(auth()->user());
                    $model->saveQuietly();
                }
            }
        });
    }

    protected static function getUserSnapshot($user): array
    {
        return [
            'employee_id'   => $user->employee_id,
            'email_address' => $user->email_address,
            'full_name'     => $user->full_name,
            'mobile_no'     => $user->mobile_no,
        ];
    }
}
