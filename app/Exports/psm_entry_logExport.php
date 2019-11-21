<?php

namespace App\Exports;

use App\EntryLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use PHPExcel_Worksheet_Drawing;

class psm_entry_logExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    private $from,$to;
    public function __construct($from,$to)
    {
        $this->from = $from;
        $this->to = $to;

    }

    public function collection()
    {

        $from=$this->from;
        $to=$this->to;
        $aa=EntryLog::
            wherein('review_flag',[1,2,3,4,5])
            ->whereBetween('create_time', [$from, $to])->get()
            ->toArray();
        foreach($aa[0] as $key => $value)
        {
            $mykey[] = $key;
            $bb[0][$key]=$key;
        }

        $cc=array_merge($bb,$aa);


        $dd=collect($cc);
        return $dd;
    }

}
