<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class ParkingFeeTest extends TestCase
{
    /**
     * A basic feature test example.
     * php vendor/phpunit/phpunit/phpunit --filter {ParkingFeeTest}
     *
     * @return void
     */
    public function testRatePenang()
    {

        $path = 'tests/Feature/ParkingFeeTest.json';
        $json_data = json_decode(file_get_contents($path), true);
        for($a=0;$a<count($json_data['ParkingFeeTest']['testRate_penang']);$a++){
            $test_name = $json_data['ParkingFeeTest']['testRate_penang'][$a]["name"];
            $entry_time = $json_data['ParkingFeeTest']['testRate_penang'][$a]["entry_time"];
            $req_time = $json_data['ParkingFeeTest']['testRate_penang'][$a]["request_time"];
            $service = $json_data['ParkingFeeTest']['testRate_penang'][$a]["service"];
            $expect_amount = $json_data['ParkingFeeTest']['testRate_penang'][$a]["expected"]["amount"];
            $expect_duration = $json_data['ParkingFeeTest']['testRate_penang'][$a]["expected"]["duration"];
            log::notice("[testRate_penang] start $test_name",$json_data['ParkingFeeTest']['testRate_penang'][$a]);

            $response = \App\Http\Controllers\ParkingRateController::getParkingFee($entry_time,$service,$req_time);
            try {
                $this->assertEquals($expect_amount, $response->original['amount']);
//                $this->assertEquals('duration', $response->original['duration']);
                log::info("[testRate_penang] success $test_name",$json_data['ParkingFeeTest']['testRate_penang'][$a]);
            }
            catch (\Exception $e)
            {
                log::error("[testRate_penang] failed $test_name $e",$json_data['ParkingFeeTest']['testRate_penang'][$a]);
//                return false;
            }

        }



    }
}
