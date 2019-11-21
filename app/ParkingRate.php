<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ParkingRate extends Model
{
  protected $fillable = [
      'type',
      'location',
      'rate_per_hour',
      'subsequent_hours',
      'max_rate',
      'rate_per_entry',
      'start_rate_per_entry',
      'end_rate_per_entry'
  ];

  protected $hidden = ['created_at', 'updated_at'];

  protected $casts = ['rate_per_hour' => 'array'];

  public function events(){
      return $this->belongsToMany('\App\ParkingEvent', 'parking_event_rates', 'rate_id', 'event_id');
  }

  public function scopeLocation($query, $location)
  {
      return $query->when($location, function ($query) use ($location) {
          return $query->where('location', $location);
      });
  }


  /* public function scopePackage($query, $package_id)
  {
      $query  =  $package_id
                ? $query->where('package_id', $package_id)
                : $query->whereNull('package_id');

      return $query;
  } */

  
  public function scopePackage($query, $package_id)
  {
      $query  =  "";

      return $query;
  }

  public static function byEvent($location = null, $package_id = null, $service = "TCV", $entry_time=null)
  {
		$service = $service ?? "TCV";

        $rates  =  self::where('service', $service)          
          ->get()
          ->keyBy('type');

      //$datetime = \Carbon\Carbon::now();
      $datetime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $entry_time);
      $day = $datetime->formatLocalized('%A');
      $date = $datetime->toDateString();

      $event_rate = $rates['general'] ?? null;

      if(array_has($rates, 'public holiday')){
          if($rates['public holiday']->events()->whereDate('date', $date)->exists()){
              return $rates['public holiday'];
          }
      }

      foreach($rates as $event => $rate){
          if($event == strtolower($day)){
              $event_rate = $rate;
          }
      }

      return $event_rate;
    }

    public static function getlatestfare($rates, $entry_time, $duration)
    {
        if ($rates->overnight_rates != 0) {
            return self::getOvernightRates($rates, $entry_time, $duration);
        }
        if ($rates->rate_per_entry_flag) {
          return self::getRatePerEntry($rates, $entry_time, $duration);
        }
        return self::getNormalRates($rates, $entry_time, $duration);
    }

    /*
        For calculate over night parking rates
    */

    public static function getOvernightRates($rates, $entry_time_unix, $duration)
    {
        $start_rate_per_entry = $rates->start_rate_per_entry;
        $end_rate_per_entry = $rates->end_rate_per_entry;
        $rate_per_entry = $rates->rate_per_entry;
        $rate_first_hour = $rates->rate_per_hour;
        $subsequent_rates = $rates->subsequent_rates;
        $subsequent_block = $rates->subsequent_block;
        $max_rates = $rates->max_rate;
        $overnight = true;
        $overnight_rates = $rates->overnight_rates;
        $total_amount = 0;

        $start_rate_per_entry = strtotime($start_rate_per_entry);
        $end_rate_per_entry = strtotime($end_rate_per_entry);

        if ($end_rate_per_entry != $start_rate_per_entry) {
            if ($end_rate_per_entry < $start_rate_per_entry){
              $end_rate_per_entry += 24 * 3600;
            }

            if($entry_time_unix >= $start_rate_per_entry && $entry_time_unix <= $end_rate_per_entry){
              return $rate_per_entry;
            }
        }

        $entry_time = Carbon::createFromTimestamp($entry_time_unix);

        $now_unix = $entry_time_unix + $duration;
        $now = Carbon::createFromTimestamp($now_unix);

        $minutes = $duration / 60; // convert to minutes
        $days = floor($minutes / 60 / 24); //get days
        $duration_in_parking = floor($minutes / 60) * 60; // hour in minutes
        $duration_in_parking += ($minutes % 60);  // add extra minutes

        $period = new \Carbon\CarbonPeriod($entry_time->copy()->midDay(), '1 day', $now);
        $max_rates = $overnight_rates;
        $period = $period->toArray();

        if (empty($period)) {
            $period[] = $now->midDay();
        }

        foreach ($period as $index => $date) {
            $data[] = $date;
            if ($index == 0) {
                // check if the this is the only loop
                if (end($period) == $period[$index]) {
                    $duration = $now_unix - $entry_time_unix;

                    $minutes = $duration / 60; // convert to minutes
                    $duration_in_parking = floor($minutes / 60) * 60; // hour in minutes
                    $duration_in_parking += ($minutes % 60);  // add extra minutes
                    $amount = 0;

                    foreach($rate_first_hour as $minutes => $rates)
                    {
                        if ($minutes == "10000000000") {
                            return   $rates;
                        }
                        if ($minutes < $duration_in_parking) {
                            $amount += $rates;
                            $duration_in_parking -= $minutes;
                        } else {
                            $amount += $rates;
                            $duration_in_parking -= $duration_in_parking;
                        }
                    }

                    $amount += ceil($duration_in_parking / $subsequent_block) * $subsequent_rates;
                    $total_amount = $amount;

                    if ($now >= $date && $date->copy()->addMinutes(15) >= $now && $total_amount >= $max_rates) {
                        $total_amount = $max_rates;
                    }

                    continue;
                }

                if ($date >= $entry_time) {
                    $duration = strtotime($date) - $entry_time_unix;
                } else {
                    // skip this loop because it already more than 12 pm
                    continue;
                }

                $minutes = $duration / 60; // convert to minutes
                $duration_in_parking = floor($minutes / 60) * 60; // hour in minutes
                $duration_in_parking += ($minutes % 60);  // add extra minutes
                $amount = 0;

                foreach($rate_first_hour as $minutes => $rates)
                {
                    if ($minutes == "10000000000"){
                        return $rates;
                    }
                    if ($minutes < $duration_in_parking){
                        $amount += $rates;
                        $duration_in_parking -= $minutes;
                    } else {
                        $amount += $rates;
                        $duration_in_parking -= $duration_in_parking;
                    }
                }

                $total_amount += $amount + ceil($duration_in_parking / $subsequent_block) * $subsequent_rates;

                // Check total amount before 12 is it more than 85
                // More than 85 it will return 85
                // Less than  85 will return 0
                if ($total_amount >= $max_rates) {
                    $total_amount = $max_rates;
                } else {
                  $total_amount = 0;
                }
                continue;
            }

            if (end($period) == $period[$index]) {
                if ($now >= $date) {
                    $duration = strtotime($now) - strtotime($date);

                    $minutes = $duration / 60; // convert to minutes
                    $duration_in_parking = floor($minutes / 60) * 60; // hour in minutes
                    $duration_in_parking += ($minutes % 60);  // add extra minutes

                    $amount = 0;
                    if ($now->diffInMinutes($date) > 15) {
                        $amount = ceil($duration_in_parking / $subsequent_block) * $subsequent_rates;
                    }

                    $total_amount += $amount + $max_rates;
                } else {
                    $date = $period[$index - 1]; //get yesterday date;
                    $duration =  strtotime($now) - strtotime($date);

                    $minutes = $duration / 60; // convert to minutes
                    $duration_in_parking = floor($minutes / 60) * 60; // hour in minutes
                    $duration_in_parking += ($minutes % 60);  // add extra minutes

                    $amount = ceil($duration_in_parking / $subsequent_block) * $subsequent_rates;

                    $total_amount += $amount;
                }
            } else {
                $total_amount += $max_rates;
            }
        }

        // dd($total_amount, $data);
        return $total_amount;
    }

    /*
        For calculate parking rates per entry
    */

    public static function getRatePerEntry($rates, $entry_time_unix, $duration)
    {
        $start_rate_per_entry = $rates->start_rate_per_entry;
        $end_rate_per_entry = explode(" ", $rates->end_rate_per_entry)[0];
        $rate_per_entry = $rates->rate_per_entry;
        $total_amount = 0;

        $entry_time = Carbon::createFromTimestamp($entry_time_unix);
        $date_from = $entry_time->format('Y-m-d');

        $now = Carbon::createFromTimestamp($entry_time_unix + $duration);

        $period = CarbonPeriod::create($date_from . ' ' . $start_rate_per_entry, $now);
        $period = $period->toArray();

        $count = count($period);
        if ($period[0] > $entry_time) {
            ++$count;
        }

        return $count * $rate_per_entry;
    }

    /*
        For calculate normal parking rates
    */

    public static function getNormalRates($rates, $entry_time, $duration)
    {
        $start_rate_per_entry = $rates->start_rate_per_entry;
        $end_rate_per_entry = $rates->end_rate_per_entry;
        $rate_per_entry = $rates->rate_per_entry;
        $rate_first_hour = $rates->rate_per_hour;
        $subsequent_rates = $rates->subsequent_rate;
        $subsequent_block = $rates->subsequent_block;
        $max_rate = $rates->max_rate;
        $total_amount = 0;

        $start_rate_per_entry = strtotime($start_rate_per_entry);
        $end_rate_per_entry = strtotime($end_rate_per_entry);

        if ($end_rate_per_entry != $start_rate_per_entry) {
            if ($end_rate_per_entry < $start_rate_per_entry){
              $end_rate_per_entry += 24 * 3600;
            }

            if($entry_time >= $start_rate_per_entry && $entry_time <= $end_rate_per_entry){            
              return $rate_per_entry;
            }
        }

        $minutes = $duration / 60; // convert to minutes
        $days = floor($minutes / 60 / 24); //get days
        $duration = floor($minutes / 60) * 60; // hour in minutes
        $duration += ($minutes % 60);  // add extra minutes

        $total_amount = $days * $max_rate;
        $duration = $duration - (1440 * $days);

        $amount = 0;
        foreach($rate_first_hour as $minutes => $rates)
        {   
            if ($minutes == "10000000000"){
                return $rates;
            }
            if ($minutes <= $duration){
                $amount += $rates;
                $duration -= $minutes;
            } else {
                return $total_amount += $rates;
            }
        }

        $amount += ceil($duration / $subsequent_block) * $subsequent_rates;
        // dd($rates, $subsequent_rates);

        if($amount >= $max_rate)
            return $total_amount += $max_rate;

        $total_amount += $amount;

        return $total_amount;
    }

    //add new parking rates
    public static function store($request)
    {
        $request = (object) $request;

        $rate_per_hour =  json_decode($request->rate_per_hour);
       
        $rate = new \App\ParkingRate();

        $rate->type = $request->type;
        $rate->subsequent_block = $request->time_block;       
        $rate->subsequent_rate = $request->subsequent_rate;
        $rate->max_rate = $request->max_rate;
        $rate->overnight_rates = $request->overnight_rate;
        $rate->rate_per_entry = $request->rate_per_entry;
        $rate->start_rate_per_entry = $request->rate_per_entry_start;
        $rate->end_rate_per_entry = $request->rate_per_entry_end;
        $rate->entry_period = $request->entry_period;
        $rate->service = $request->service;
        $rate->rate_per_hour = $rate_per_hour;        
        $rate->entry_grace = $request->entry_grace;
        $rate->exit_grace = $request->exit_grace;
        $rate->save();

        return true;

    }

    //add new update parking rates
    public static function modify($request)
    {
        $request = (object) $request;

        $rate_per_hour =  json_decode($request->rate_per_hour);
       
        $rate = \App\ParkingRate::find($request->id);

        $rate->type = $request->type;
        $rate->subsequent_block = $request->time_block;       
        $rate->subsequent_rate = $request->subsequent_rate;
        $rate->max_rate = $request->max_rate;
        $rate->overnight_rates = $request->overnight_rate;
        $rate->rate_per_entry = $request->rate_per_entry;
        $rate->start_rate_per_entry = $request->rate_per_entry_start;
        $rate->end_rate_per_entry = $request->rate_per_entry_end;
        $rate->entry_period = $request->entry_period;
        $rate->service = $request->service;
        $rate->rate_per_hour = $rate_per_hour;
        $rate->entry_grace = $request->entry_grace;
        $rate->exit_grace = $request->exit_grace;
        $rate->save();

        return true;

    }

}
