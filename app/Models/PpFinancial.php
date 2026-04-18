<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpFinancial extends Model
{
    protected $fillable = [
        'finance_code',
        'contract_id',
        'pp_project_id',
        'as_of_date',
        'committed_amount',
        'paid_to_date',
        'currency',
        'notes',
        'entered_by',
    ];

    protected $casts = [
        'as_of_date'       => 'date',
        'committed_amount' => 'decimal:2',
        'paid_to_date'     => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $financial) {
            if (empty($financial->finance_code)) {
                $driver = $financial->getConnection()->getDriverName();
                $orderBy = $driver === 'oracle'
                    ? "TO_NUMBER(SUBSTR(finance_code, 5)) DESC"
                    : "CAST(SUBSTRING(finance_code, 5) AS UNSIGNED) DESC";

                $last = static::where('finance_code', 'like', 'FIN-%')
                    ->orderByRaw($orderBy)
                    ->value('finance_code');

                $next = $last ? ((int) substr($last, 4)) + 1 : 1;
                $financial->finance_code = 'FIN-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PpProject::class, 'pp_project_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
