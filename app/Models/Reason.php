<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    use HasFactory;

    protected $with = ["user"];

    protected $guarded = [];

    protected $casts = [
        "created_at" => "datetime:d-M-y"
    ];

    /**
     * Get the user that owns the Reason
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
