<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['time_in_numbers'];

    protected $with = ['shift_type'];

    protected $casts = [
        'days' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        // 'company_id',
        'branch_id',
        'shift_type_id',
    ];

    public function shift_type()
    {
        return $this->belongsTo(ShiftType::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('id', 'desc');
        });
    }

    public function autoshift()
    {
        return $this->hasOne(AutoShift::class);
    }

    public function getTimeInNumbersAttribute()
    {
        return strtotime($this->on_duty_time);
        return date("Y-m-d H:i", strtotime($this->on_duty_time));
    }
}
