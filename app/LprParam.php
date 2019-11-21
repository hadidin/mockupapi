<?php

namespace App;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

class LprParam extends Model
{
    protected $table = 'psm_lpr_param';

    public static function get_param($name)
    {
        $data = self::where('param_name', $name)->where('flag', '1')->value('value1');
        return $data;
    }
    public function update_lpr_operation($new_config)
    {
        DB::enableQueryLog();
        DB::table($this->table)
            ->where('param_name', 'lpr_operation')
            ->where('flag', '1')
            ->update(
                [
                    'value1' => $new_config,
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
    }
}
