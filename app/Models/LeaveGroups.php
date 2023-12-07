<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveGroups extends Model
{
    use HasFactory;
    protected $table = 'leave_groups';
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:d-M-y',
    ];

    // public function leave_type()
    // {
    //     return $this->belongsTo(LeaveType::class)->withDefault([
    //         "name" => "---", "short_name" => "---",
    //     ]);
    // }
    // public function leave_count()
    // {
    //     return $this->belongsTo(LeaveCount::class, 'id', 'group_id');
    // }
    public function leave_count()
    {
        return $this->hasMany(LeaveCount::class, "group_id", "id");
    }
    protected static function boot()
    {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('id', 'desc');
        });
    }
}
