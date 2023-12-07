<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function company_id()
    {
        return $this->belongsTo(Company::class);
    }

    protected $casts = [
        "interval" => "array",
        "scheduled_days" => "array",
        "json" => "array",
    ];
}
