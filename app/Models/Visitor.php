<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_name', 'name_with_user_id'];

    protected $casts = [
        "created_at" => "datetime:d-M-Y",
    ];

    public function getLogoAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('media/visitor/logo/' . $value);
    }

    /**
     * Get the user that owns the Visitor
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id', 'timezone_id')->withDefault([
            "timezone_name" => "---",
        ]);
    }


    public function getNameWithUserIDAttribute()
    {
        return $this->first_name . " - " . $this->system_user_id;
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }
}
