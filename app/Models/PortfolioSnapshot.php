<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'total_projects',
        'total_committed',
        'total_paid',
        'avg_progress',
        'total_risks',
        'high_risks',
    ];

    protected $casts = [
        'snapshot_date'    => 'date',
        'total_committed'  => 'decimal:2',
        'total_paid'       => 'decimal:2',
        'avg_progress'     => 'decimal:1',
    ];
}
