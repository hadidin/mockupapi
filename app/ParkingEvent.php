<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ParkingEvent extends Model
{
  protected $fillable = [
      'name',
      'date',
      'country'
  ];

  protected $casts = ['rate_per_hour' => 'array'];
}
