<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryNumberSetting extends Model
{
    protected $fillable = ['prefix', 'include_item_code'];

    protected $casts = ['include_item_code' => 'boolean'];
}
