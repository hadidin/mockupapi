<?php

namespace App\Exports;

use App\SmcHolderInfo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;

class psm_smc_holder_infoExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    use Exportable;

    public function collection()
    {

        $aa=SmcHolderInfo::all()->toArray();
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
