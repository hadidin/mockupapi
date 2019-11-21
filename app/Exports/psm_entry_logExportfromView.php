<?php

namespace App\Exports;

use App\EntryLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use PHPExcel_Worksheet_Drawing;


class psm_entry_logExportfromView implements FromView
{
    private $from,$to;
    public function __construct($from,$to)
    {
        $this->from = $from;
        $this->to = $to;

    }

    public function view(): View
    {
        $from=$this->from;
        $to=$this->to;
        $lpr_backend_server=config('custom.lpr_backend_host');
        $lpr_backend_port=config('custom.lpr_backend_port');
        $lpr_url=$lpr_backend_server.':'.$lpr_backend_port;

        $aa=EntryLog::
        where('review_flag',1)
            ->whereBetween('create_time', [$from, $to])
            ->orderBy('create_time', 'desc')
            ->get()
            ->toArray();
//        dd($aa);
//        print_r($aa);die;

        return view('exports.logs_reviewed')
            ->with('entry_log_db_list',$aa)
            ->with('lpr_backend_server_base_url',$lpr_url);




    }
}
