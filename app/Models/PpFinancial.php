<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpFinancial extends Model
{
    protected $fillable = [
        'finance_code',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(PpProject::class, 'pp_project_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
