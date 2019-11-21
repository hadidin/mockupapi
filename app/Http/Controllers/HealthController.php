<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

define('INDEX_KP_LOCALPSM','KP-LOCALPSM');
define('INDEX_CPU','CPU');
define('INDEX_MEMORY','MEMORY');
define('INDEX_DISK','DISK');
define('INDEX_MYSQL','MYSQL');
define('INDEX_LPR_SERVER','LPR-SERVER');
define('INDEX_CAMERA','CAMERA');

class HealthController extends Controller
{
    /**
    * get the cpu usage
    */
    protected function getCpuUsage() {
       $exec_loads = sys_getloadavg();
       $exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
       $cpu_usage = round($exec_loads[1]/($exec_cores + 1)*100, 0);
       $index = array(
           'name' => INDEX_CPU,
           'id' => INDEX_CPU,
           'value' => $cpu_usage,
           'description' => "cpu usage is $cpu_usage%",
       );
       return $index;
   }

    /**
    * get the memory usage index
    */
    protected function getMemoryUsage() {
        $exec_free = explode("\n", trim(shell_exec('free')));
        $get_mem = preg_split("/[\s]+/", $exec_free[1]);
        $mem_usage = round($get_mem[2]/$get_mem[1]*100, 0);
        $index = array(
            'name' => INDEX_MEMORY,
            'id' => INDEX_MEMORY,
            'value' => $mem_usage,
            'description' => "memory usage is $mem_usage%",
        );
        return $index;
    }

    /**
    * get the disk usage index
    */
    function getDiskUsage() {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $disk_usage = round(($total-$free)/$total * 100,0);
        $index = array(
            'name' => INDEX_DISK,
            'id' => INDEX_DISK,
            'value' => $disk_usage,
            'description' => "disk usage is $disk_usage%",
        );
        return $index;
    }
    
    /**
     * check lpr server status
     */
    protected function checkLprServer() {
        $curl = curl_init();
        // TODO, get the lpr server host from config file
        $lpr_server_host = "http://127.0.0.1:8080";
        $url = "$lpr_server_host/v1/device/operation";
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
	    curl_setopt($curl, CURLOPT_POST, TRUE);
	    $post_data = array();
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            curl_close($curl);
            return 0;
        }
        curl_close($curl);
        return 1;
    }    

    /**
     * get the lpr server status
     */
    function getLprServerStatus() {
	$value = $this->checkLprServer();
        $index = array(
            'name' => INDEX_LPR_SERVER,
            'id' => INDEX_LPR_SERVER,
            'value' => $value,
            'description' => 'lpr server status is '.($value==1?'online':'offline'),
        );
        return $index;
    }

    /**
     * get the lpr camera and mysql status
     */
    function getCamerasStatus() {
        $indexArray = array();
        $lanes = array();
        $mysqlState = 0;
        try {
            $lanes = \App\LaneConfig::getAllLanes();
            $mysqlState = 1;
        } catch (\Exception $e) {
	}
        // put the mysql state first
        $indexArray[] = array(
            'name' => INDEX_MYSQL,
            'id' => INDEX_MYSQL,
            'value' => $mysqlState,
            'description' => 'MySQL status is '.($mysqlState==1?'online':'offline'),
        );
	    foreach($lanes as $lane) {
            $index = array(
                'name' => INDEX_CAMERA,
		        'id' => $lane->camera_sn,
                'value' => $lane->camera_state=='online'?1:0,
                'description' => "camera status is {$lane->camera_state}",
            );
            $indexArray[] = $index;
        }
        return $indexArray;
    }

    /**
     * check service health
     * 
     * @param \Illuminate\Http\Request $request the http get request
     * 
     * @return \Illuminate\Http\Response
     */
    public function checkHealth(Request $request) {

        $respData = array();
        $respData['name'] = INDEX_KP_LOCALPSM;
        // TODO,get site id from config file
        $site_id = 'SIG0079';
        $respData['id'] = $site_id;
        $respData['description'] = 'kiplePark local parking system';
        $subServices = array();
        $subServices[] = $this->getCpuUsage();
        $subServices[] = $this->getMemoryUsage();
        $subServices[] = $this->getDiskUsage();
	    $subServices[] = $this->getLprServerStatus();
	    $cameras = $this->getCamerasStatus();
	    foreach($cameras as $c) {
		    $subServices[] = $c;
	    }
        $respData['subServices'] = $subServices;
        // follow the standard API response code
        $resp = array(
            'success' => true,
            'code' => 'success',
            'message' => 'success',
            'data' => $respData,
        );
        return response()->json($resp);
    }
}
