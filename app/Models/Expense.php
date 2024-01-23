<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeFilter($query, $startDate, $endDate)
    {
        return $query
            ->where("user_id", request("user_id") ?? 0)
            ->whereBetween('date', [$startDate, $endDate]);
    }
}
