<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostCompany extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = "host_companies";

    public function getLogoAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('media/company/logo/' . $value);
    }
}
