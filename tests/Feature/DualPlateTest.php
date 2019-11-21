<?php

namespace Tests\Feature;

use PHPUnit\Framework\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DualPlateTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * 1) php artisan serve
     * 2) php vendor/phpunit/phpunit/phpunit
     * 3) set your simulator correctly
     *
     *
     * @return void
     */

    /** @test */
    public function entry_single_cam1_sent_visitor_plate_no()
    {
        $ini_array = parse_ini_file("test_config.ini",true);
        $plate_no = $ini_array['dual_plate_test']['PLATE_VISITOR1'];
        $url = $ini_array['common']['URL'];

        //get camera/lane info
        $unit_test_get_lane_entry = \App\Http\Controllers\Unittest\r1::unit_test_get_all_lanes(0);
        $unit_test_get_lane_entry_array = json_decode(json_encode($unit_test_get_lane_entry),true);
        log::info("lane info= ",$unit_test_get_lane_entry_array);

        //get entry
        $cam_sn = $unit_test_get_lane_entry_array[0]['camera_sn'];
        $dvd_id = "DVDFEFD0";
        $post_data = \App\Http\Controllers\Unittest\r1::generate_parameter($plate_no,$cam_sn,$dvd_id);
        $cam1 = \App\Http\Controllers\Unittest\r1::post_request_api($post_data,$url."/api/lpr/push_plate_no");
        $this->assertArrayHasKey('status', $cam1);
        $this->assertEquals('TK', $cam1['ls_response']);
        $this->assertEquals('LS010', $cam1['check_result']);

    }
    /** @test */
    public function exit_cam1_sent_visitor_plate_no()
    {
        $ini_array = parse_ini_file("test_config.ini",true);
        $plate_no = $ini_array['dual_plate_test']['PLATE_VISITOR1'];
        $url = $ini_array['common']['URL'];

        //get camera/lane info
        $unit_test_get_lane_entry = \App\Http\Controllers\Unittest\r1::unit_test_get_all_lanes(1);
        $unit_test_get_lane_entry_array = json_decode(json_encode($unit_test_get_lane_entry),true);
        log::info("lane info= ",$unit_test_get_lane_entry_array);

        //get entry
        $cam_sn = $unit_test_get_lane_entry_array[0]['camera_sn'];
        $dvd_id = "DVDFEFD0";
        $post_data = \App\Http\Controllers\Unittest\r1::generate_parameter($plate_no,$cam_sn,$dvd_id);
        $cam1 = \App\Http\Controllers\Unittest\r1::post_request_api($post_data,$url."/api/lpr/push_plate_no");
        $this->assertArrayHasKey('status', $cam1);
        $this->assertEquals('SE', $cam1['ls_response']);
        $this->assertEquals('LS011', $cam1['check_result']);
    }
    /** @test */
    public function entry_single_cam1_sent_season_plate_no()
    {
        //get season plate no
        $ini_array = parse_ini_file("test_config.ini",true);
        $plate_no = $ini_array['dual_plate_test']['SEASON_BINDED1'];
        $url = $ini_array['common']['URL'];

        //get camera/lane info
        $unit_test_get_lane_entry = \App\Http\Controllers\Unittest\r1::unit_test_get_all_lanes(0);
        $unit_test_get_lane_entry_array = json_decode(json_encode($unit_test_get_lane_entry),true);
        log::info("lane infoxxxxxxxxxx= ",$unit_test_get_lane_entry_array);

        //get entry
        $cam_sn = $unit_test_get_lane_entry_array[0]['camera_sn'];
        $dvd_id = "DVDFEFD0";
        $post_data = \App\Http\Controllers\Unittest\r1::generate_parameter($plate_no,$cam_sn,$dvd_id);
        $cam1 = \App\Http\Controllers\Unittest\r1::post_request_api($post_data,URL."/api/lpr/push_plate_no");
        $this->assertArrayHasKey('status', $cam1);
        $this->assertEquals('LS006', $cam1['check_result']);
        $this->assertEquals(1, $cam1['parking_type']);
        $this->assertEquals(1, $cam1['season_check_result']);

    }
    /** @test */
    public function exit_single_cam1_sent_season_plate_no()
    {
        //get season plate no
        $ini_array = parse_ini_file("test_config.ini",true);
        $plate_no = $ini_array['dual_plate_test']['SEASON_BINDED1'];
        $url = $ini_array['common']['URL'];

        //get camera/lane info
        $unit_test_get_lane_entry = \App\Http\Controllers\Unittest\r1::unit_test_get_all_lanes(1);
        $unit_test_get_lane_entry_array = json_decode(json_encode($unit_test_get_lane_entry),true);
        log::info("lane info= ",$unit_test_get_lane_entry_array);

        //get entry
        $cam_sn = $unit_test_get_lane_entry_array[0]['camera_sn'];
        $dvd_id = "DVDFEFD0";
        $post_data = \App\Http\Controllers\Unittest\r1::generate_parameter($plate_no,$cam_sn,$dvd_id);
        $cam1 = \App\Http\Controllers\Unittest\r1::post_request_api($post_data,$url."/api/lpr/push_plate_no");
        $this->assertArrayHasKey('status', $cam1);
        $this->assertEquals('LS009', $cam1['check_result']);
        $this->assertEquals(1, $cam1['parking_type']);
        $this->assertEquals(13, $cam1['season_check_result']);
    }

//    /** @test */
    public function dual_cam_request_with_one_season_user(){

        $ini_array = parse_ini_file("test_config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];

        $client = new \GuzzleHttp\Client();
        $plate_no = "ABC1234";
        $cam_sn = "deff75cb-3dd31162";
        $dvd_id = "DVDFEFD0";
        $post_data = \App\Http\Controllers\API\CameraPushController::generate_parameter($plate_no,$cam_sn,$dvd_id);
        $body = \GuzzleHttp\Psr7\stream_for($post_data);

        $promise1 = $client->getAsync('POST', 'http://localhost:8000/api/lpr/push_plate_no', ['body' => $body, 'headers'  => [
            'Content-Type' => 'application/json']])->then(
            function ($response) {
                return $response->getBody();
            }, function ($exception) {
            return $exception->getMessage();
        }
        );

        $plate_no = "ABC123";
        $cam_sn = "deff75cb-3dd31162";
        $dvd_id = "DVDFEFD0";
        $post_data = \App\Http\Controllers\API\CameraPushController::generate_parameter($plate_no,$cam_sn,$dvd_id);
        $body = \GuzzleHttp\Psr7\stream_for($post_data);

        $promise2 = $client->getAsync('POST', 'http://localhost:8000/api/lpr/push_plate_no', ['body' => $body, 'headers'  => [
            'Content-Type' => 'application/json']])->then(
            function ($response) {
                return $response->getBody();
            }, function ($exception) {
            return $exception->getMessage();
        }
        );

        $response1 = $promise1->wait();
        $response2 = $promise2->wait();

        log::info("lllllll",[$response1,$response2]);

    }

}
