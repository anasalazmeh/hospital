<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Specialties extends Model
{
  use HasFactory;
  protected $table = 'specialties';
  protected $fillable = [
    'name',
    'description'
  ];

  public function dectors()
  {
    return $this->belongsToMany(DashboardAccounts::class, 'user_specialty', "specialty_id", "user_account_id");
  }
  public function intensiveCarePatient()
  {
    return $this->belongsToMany(IntensiveCarePatient::class, 'icps');
  }
}
