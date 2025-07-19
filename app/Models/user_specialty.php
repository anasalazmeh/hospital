<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class user_specialty extends Model
{
    use HasFactory;
    protected $table = 'specialties';
    protected $fillable = [
      'user_account_id','specialty_id'
    ];
  //   public function DashboardAccounts(){
  //     return $this->belongsTo(DashboardAccounts::class,'id', 'user_account_id');
  // }
  //   public function Specialties(){
  //     return $this->belongsTo(Specialties::class, 'id','specialty_id');
  // }
}
