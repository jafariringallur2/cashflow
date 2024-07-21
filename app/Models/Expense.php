<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;


    protected $fillable = ['amount','date','item_id','note','created_for_id','created_by_id'];
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
