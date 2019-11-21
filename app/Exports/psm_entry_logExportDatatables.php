<?php

namespace App\Exports;

use App\EntryLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;

class psm_entry_logExportDatatables implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
 
    private $lane_to_be_view,$review_flag,$plate_search, $season_only, $from, $to;
    public function __construct($lane_to_be_view,$review_flag,$plate_search, $season_only, $from, $to)
    {
        $this->lane_to_be_view = $lane_to_be_view;
        $this->review_flag = $review_flag;
        $this->plate_search = $plate_search;
        $this->season_only = $season_only;
        $this->from = $from;
        $this->to = $to;

    }


    public function collection()
    {
        $lane_to_be_view=$this->lane_to_be_view;
        $review_flag=$this->review_flag;
        $plate_search=$this->plate_search;
        $plate_search=$this->plate_search;
        $season_only=$this->season_only;
        $from=$this->from;
        $to=$this->to;

 
        $posts = EntryLog::total_car_db($lane_to_be_view,$review_flag,$plate_search, $season_only, $from, $to)->get();

 
        return $posts;
    }

    public function headings(): array
    {
        return [
            'id', 
            'lane_id', 
            'camera_sn', 
            'car_color', 
            'plate_no', 
            'plate_no_reviewed', 
            'small_picture', 
            'big_picture', 
            'qr_sn', 
            'qr_code', 
            'in_out_flag', 
            'is_success', 
            'failed_remark', 
            'is_season_subscriber', 
            'sync_status', 
            'datetime_sync_kp_cloud', 
            'leave_type', 
            'review', 
            'review_season_subscriber', 
            'review_flag', 
            'create_time'
        ];
    }
}
