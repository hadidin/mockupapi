"use strict";

const $picture_url_prefix = "img";

// season check result define
const $season_check_result = {
    default: -1,
    entry_none: 0,
    entry_success: 1,
    entry_expired: 2,
    entry_secondary: 3,
    entry_duplicate_no: 4,
    entry_inactive: 5,
    entry_access_denied: 6,
    exit_none: 10,
    exit_no_entry: 11,
    exit_car_locked: 12,
    exit_success: 13
};

// visitor check result define
const $visitor_check_result = {
    default: -1,
    entry_none: 0,
    entry_success: 1,
    entry_duplicate_no: 2,
    entry_sys_error: 3,
    exit_none: 10,
    exit_unpaid: 11,
    exit_ticket_used: 12,
    exit_exceed_grace_period: 13,
    exit_success: 14,
    exit_sys_error: 15,
    exit_command_rejected: 16,
    exit_ticket_not_found: 17,
    exit_comm_error: 18,
};

function ld_notify($message,$type) {
    $.notify(
        {
            message: $message,
        },{
            type:$type,
            element: 'body',
        }
    );
}

function ld_get_led_string($exit, $entry) {
    return 'GOOD BYE<br> BYE BYE';
}

function ld_get_exit_result($exit) {
    if (typeof($exit) == "undefined") {
        return 'N/A';
    }
  
    if ($exit.season_check_result == 13 || $exit.visitor_check_result == 14) {
        return 'Success'
    }
    return 'Failed'
}

function ld_get_blocked_reason($exit) {
    if (typeof($exit) == "undefined") {
        return 'N/A';
    }    
    var $i,$season_result_strings = [
        [10,'none',''],
        [11,'season no entry','No Entry'],
        [12,'season car locked','Car Locked'],
        [13,'season exit success',''],
    ];
    for($i=0;$i<$season_result_strings.length;$i++) {
        var $record = $season_result_strings[$i];
        if($exit.season_check_result==$record[0]) {
            return $record[2];
        }
    }
    var $visitor_result_strings = [
        [10,'none',''],
        [11,'visitor exit unpaid','Unpaid'],
        [12,'visitor exit ticket used','Ticket Used'],
        [13,'visitor exit exceed grace period','Exceed Grace Peirod'],
        [14,'visitor exit success',''],
        [15,'visitor sys error','System Error'],
        [16,'visitor exit command rejected','No Vehicle Detected'],
        [17,'visitor exit ticket not found','Ticket Not Found'],
        [18,'visitor exit comm error','Other Error'],
    ];
    for($i=0;$i<$visitor_result_strings.length;$i++) {
        var $record = $visitor_result_strings[$i];
        if($exit.visitor_check_result==$record[0]) {
            return $record[2];
        }
    }
    return 'N/A';
}

function ld_get_entry_category($entry) {
    if (typeof($entry) == "undefined") {
        return 'N/A';
    }    
    var $i,$season_result_strings = [
        [-1,'default',0],
        [0,'none',0,'No Season'],
        [1,'season entry success',1,'Season Success'],
        [2,'season entry expired',1,'Season Expired'],
        [3,'season entry secondary',1,'Season Secondary'],
        [4,'season entry duplicate number',1,'Season Duplicated Palte No'],
        [5,'season entry deactive',1,'Season Deactive'],
        [6,'season entry access denied',1,'Season Access Denied']   
    ];
    for($i=0;$i<$season_result_strings.length;$i++) {
        var $record = $season_result_strings[$i];
        if($entry.season_check_result==$record[0] && $record[2]==1) {
            return $record[3];
        }
    }
    var $visitor_result_strings = [
        [-1,'default'],
        [0,'none','No Visitor'],
        [1,'visitor entry success','Visitor Success'],
        [2,'visitor entry  duplicate number','Visitor Duplciated Plate No'],
        [3,'visitor entry sys error','Visitor System Error'],
    ];
    for($i=0;$i<$visitor_result_strings.length;$i++) {
        var $record = $visitor_result_strings[$i];
        if($entry.visitor_check_result==$record[0]) {
            return $record[2];
        }
    }
    return 'N/A';
}


function ld_create_exit_buttons($div, $exit_array) {
    var $i = 0;
    var $div_inner_html = "<div class=\"toolbar__nav\">"
    for ($i = 0; $i < $exit_array.length; $i++) {
        var $exit = $exit_array[$i];
        var $button_html = "<a href=\"javascript:ld_on_button_clicked('exit','" + $exit.id + "')\" id=\"" + $exit.id + "\">" + $exit.name + "</a>"
        $div_inner_html += $button_html;
    }
    $div_inner_html += "</div>"
    $('#' + $div).html($div_inner_html);
}

function ld_clear_last_exit($lane_name) {
    // set the lane name
    var $div_inner_html = "<div class=\"card-header\"><h6 class=\"card-title\">";
    $div_inner_html += $lane_name;
    $div_inner_html += "</h6></div>"
    // set the lane image
    $div_inner_html += "<img class=\"card-img-top\" src=\"";
    $div_inner_html += "img/no_image.png";
    $div_inner_html += "\">"
    $div_inner_html += "<div class = \"card-body\"><table style = \"border-spacing:0px 10px;\"><tbody>";
    // set all the data;
    var $i = 0;
    var $data_array = [
        { label: "Plate No", field: 'N/A' },
        { label: "DateTime", field: 'N/A' },
        { label: "Exit Result", field: 'N/A' },
        { label: "Blocked Reason", field: 'N/A' },
        { label: "Category", field: 'N/A' },
        { label: "SeasonCard", field: 'N/A' },
        { label: "Ticket", field: 'N/A' },
        { label: "Remark", field: 'N/A' },
    ];
    for ($i = 0; $i < $data_array.length; $i++) {
        $div_inner_html += "<tr><td>";
        $div_inner_html += $data_array[$i].label;
        $div_inner_html += "</td><td><span class=\"badge badge-light\">";
        $div_inner_html += $data_array[$i].field;
        $div_inner_html += "</span></td></tr>";
    }
    $div_inner_html += "</tbody></table></div>"
    $('#div-last-exit').html($div_inner_html);
}

function ld_get_show_image($value) {
    if (typeof($value)=='undefined' || $value=='') {
        return 'img/no_image.png';
    }
    return $picture_url_prefix + $value;
}

function ld_get_show_value($value) {
    if (typeof($value)=='undefine') {
        return 'N/A';
    }
    if ($value==null) {
        return 'N/A';
    }
    return $value;
}

var $ld_last_exit_record = null;

// create the remark select html accroding to the last exit and matched entry
function ld_create_remark_select($last_exit,$matched_entry) {
    var $html = '<select id="select-remark" class="form-control">';
    $html += '<option value="O_0"></option>';
    $html += '<option value="O_1">Other Reason</option>';
    $html += '<option value="O_2">Testing</option>';
    $html += '<option value="O_3">Illegal Reverse</option>';
    $html += '<option value="O_4">Lost Ticket</option>';
    var $i,$season_result_strings = [
        //[10,'none',''],
        [11,'season no entry','No Entry'],
        [12,'season car locked','Car Locked'],
        //[13,'season exit success',''],
    ];
    for($i=0;$i<$season_result_strings.length;$i++) {
        var $record = $season_result_strings[$i];
        if($last_exit.season_check_result==$record[0]) {
            $html += '<option value="S_' + $record[0]+ '">' + $record[2]+'</option>';
        } else {
            $html += '<option value="S_' + $record[0]+ '">' + $record[2]+'</option>';
        }
    }
    var $visitor_result_strings = [
        //[10,'none',''],
        [11,'visitor exit unpaid','Unpaid'],
        [12,'visitor exit ticket used',,'Ticket Used'],
        [13,'visitor exit exceed grace period','Exceed Grace Peirod'],
        //[14,'visitor exit success',''],
        [15,'visitor sys error','System Error'],
        [16,'visitor exit command rejected','No Vehicle Detected'],
        [17,'visitor exit ticket not found','Ticket Not Found'],
        [18,'visitor exit comm error','Other Error'],
    ];
    for($i=0;$i<$visitor_result_strings.length;$i++) {
        var $record = $visitor_result_strings[$i];
        if($last_exit.visitor_check_result==$record[0]) {
            $html += '<option value="V_' + $record[0]+ '">' + $record[2]+'</option>';
        }else {
            $html += '<option value="V_' + $record[0]+ '">' + $record[2]+'</option>';
        }
    }
    $html += '</select></td></tr>';
    return $html;
}

function ld_create_remark_input($last_exit,$matched_entry) {
    console.log($last_exit);
    var $i,$season_result_strings = [
        //[10,'none',''],
        [11,'season no entry','no_entry'],
        [12,'season car locked','car_locked'],
        //[13,'season exit success',''],
    ];
    var $remark = "";
    for($i=0;$i<$season_result_strings.length;$i++) {
        var $record = $season_result_strings[$i];
        if($last_exit.season_check_result==$record[0]) {
            $remark = $record[2];
            break;
        }
    }
    if ($remark=="") {
        var $visitor_result_strings = [
            //[10,'none',''],
            [11,'visitor exit unpaid','ticket_unpaid'],
            [12,'visitor exit ticket used','ticket_used'],
            [13,'visitor exit exceed grace period','ticket_exceed_gp'],
            //[14,'visitor exit success',''],
            [15,'visitor sys error','system_error'],
            [16,'visitor exit command rejected','vendor_error'],
            [17,'visitor exit ticket not found','ticket_not_found'],
            [18,'visitor exit comm error','other_error'],
        ];
        for($i=0;$i<$visitor_result_strings.length;$i++) {
            var $record = $visitor_result_strings[$i];
            if($last_exit.visitor_check_result==$record[0]) {
                $remark = $record[2];
                break;
            }
        }
    }
    if ($remark=="") {
        $remark = 'other';
    }
    var $html = '<input id="input-remark" type="hidden" value="'+$remark+'">';
    return $html;
}

function ld_set_plate_no_by_search($matched_entry) {
    $('#input-plate-no').val($matched_entry.plate_no);    
}

function ld_set_last_exit($last_exit, $matched_entry) {
    // set the lane name
    var $div_inner_html = "<div class=\"card-header\"><h6 class=\"card-title\">";
    $div_inner_html += $last_exit.lane_name;
    $div_inner_html += "</h6></div>"
    // set the lane image
    $div_inner_html += "<img class=\"card-img-top\" src=\"";
    $div_inner_html += ld_get_show_image($last_exit.big_picture);
    $div_inner_html += "\">"
    $div_inner_html += "<div class = \"card-body\"><table style = \"border-spacing:0px 10px;\"><tbody>";
    // set the Plate No with correct button
    $div_inner_html += '<tr><td>Plate No</td><td><div class="row"><div class="col-sm-8"><input id="input-plate-no" type="text" class="form-control" value="';
    $div_inner_html += $last_exit.plate_no;
    $div_inner_html += '"><i class="form-group__bar"></i></div><div class="col-sm-4"><button id="button-change" onclick="ld_on_button_clicked(\'change\',\'1\')" type="button" class="btn btn-primary btn-sm" >Change</button></div></div></td></tr>';
    // set all the data;
    var $i = 0;
    var $data_array = [
        { label: "DateTime", field: ld_get_show_value($last_exit.create_time) },
        { label: "Exit Result", field: ld_get_exit_result($last_exit) },
        { label: "Blocked Reason", field: ld_get_blocked_reason($last_exit) },
        { label: "Category", field: ld_get_entry_category($matched_entry) },
        { label: "SeasonCard", field: ld_get_show_value($matched_entry.season_holder_id) },
        { label: "Ticket", field: ld_get_show_value($matched_entry.vendor_ticket_id) },
        { label: "Details", field: ld_get_show_value($matched_entry.previous_season_entry) },
    ];
    for ($i = 0; $i < $data_array.length; $i++) {
        $div_inner_html += "<tr><td>";
        $div_inner_html += $data_array[$i].label;
        $div_inner_html += "</td><td><span class=\"badge badge-light\">";
        $div_inner_html += $data_array[$i].field;
        $div_inner_html += "</span></td></tr>";
    }
//    $div_inner_html += '<tr><td>Remark</td><td>';
//    $div_inner_html += ld_create_remark_select($last_exit,$matched_entry);
    $div_inner_html += ld_create_remark_input($last_exit,$matched_entry);
    $div_inner_html += '</tbody></table><br>';
    if ($last_exit.leave_type==0) {
        $div_inner_html += "<button id=\"button-leave\" onclick=\"ld_on_button_clicked('leave','1')\",type=\"button\" class=\"btn btn-primary btn-sm\">Manually Leave</button></div>"
    } else {
        $div_inner_html += "<button id=\"button-leave\" onclick=\"ld_on_button_clicked('leave','1')\",type=\"button\" class=\"btn btn-primary btn-sm\" disabled>Manually Leave</button></div>"
    }
    $('#div-last-exit').html($div_inner_html);
//    $("#select-remark").select2({
//        tags: true
//      });
}

function ld_clear_matched_entry() {
    // set the lane name
    var $div_inner_html = "<div class=\"card-header\"><h6 class=\"card-title\">";
    $div_inner_html += "Matched Entry Record";
    $div_inner_html += "</h6></div>"
    // set the lane image
    $div_inner_html += "<img class=\"card-img-top\" src=\"";
    $div_inner_html += "img/no_image.png";
    $div_inner_html += "\">"
    $div_inner_html += "<div class = \"card-body\"><table style = \"border-spacing:0px 10px;\"><tbody>";
    // set all the data;
    var $i = 0;
    var $data_array = [
        { label: "Lane", field: 'N/A' },
        { label: "Plate No", field: 'N/A' },
        { label: "DateTime", field: 'N/A' },
    ];
    for ($i = 0; $i < $data_array.length; $i++) {
        $div_inner_html += "<tr><td>";
        $div_inner_html += $data_array[$i].label;
        $div_inner_html += "</td><td><span class=\"badge badge-light\">";
        $div_inner_html += $data_array[$i].field;
        $div_inner_html += "</span></td></tr>";
    }
    $div_inner_html += "</tbody></table><br>";
    $div_inner_html += "<button class=\"btn btn-primary btn-sm\" data-toggle=\"modal\" data-target=\"#modal-search\">Search Similar Plate No</button>";
    $div_inner_html += "</div>";
    $('#div-matched-entry').html($div_inner_html);
}

/**
 * set matched entry UI layout according entry record
 * @param {object} $entry
 */
function ld_set_matched_entry($entry) {
    // set the lane name
    var $div_inner_html = "<div class=\"card-header\"><h6 class=\"card-title\">";
    $div_inner_html += "Matched Entry Record";
    $div_inner_html += "</h6></div>"
    // set the lane image
    $div_inner_html += "<img class=\"card-img-top\" src=\"";
    $div_inner_html += ld_get_show_image($entry.big_picture);
    $div_inner_html += "\">"
    $div_inner_html += "<div class = \"card-body\"><table style = \"border-spacing:0px 10px;\"><tbody>";
    // set all the data;
    var $i = 0;
    var $data_array = [
        { label: "Lane", field: ld_get_show_value($entry.lane_name) },
        { label: "Plate No", field: ld_get_show_value($entry.plate_no) },
        { label: "DateTime", field: ld_get_show_value($entry.create_time) },
    ];
    for ($i = 0; $i < $data_array.length; $i++) {
        $div_inner_html += "<tr><td>";
        $div_inner_html += $data_array[$i].label;
        $div_inner_html += "</td><td><span class=\"badge badge-light\">";
        $div_inner_html += $data_array[$i].field;
        $div_inner_html += "</span></td></tr>";
    }
    $div_inner_html += "</tbody></table><br>";
    $div_inner_html += "<button class=\"btn btn-sm btn-primary\" data-toggle=\"modal\" data-target=\"#modal-search\">Search Similar Plate No</button>";
    $div_inner_html += "</div>";
    $('#div-matched-entry').html($div_inner_html);
}

function ld_set_search_result($text) {
    var $div_inner_html = "<p>" + $text + "</p>";
    $('#div-search-result').html($div_inner_html);
    console.log('ld_set_search_result');

}

function ld_set_search_result_records($entry_array) {
    var $div_inner_html = "<div class=\"row\">";
    var $i = 0;
    for ($i = 0; $i < $entry_array.length; $i++) {
        $div_inner_html += "<div class=\"col-sm-6\" style = \"padding-bottom:6px;\">";
        $div_inner_html += "<a href=\"javascript:ld_on_button_clicked('result','" + $entry_array[$i].id + "')\">";
        $div_inner_html += "<img class=\"card-img-top\" src=\"";
        $div_inner_html += ld_get_show_image($entry_array[$i].big_picture);
        $div_inner_html += "\"></a></div>";
    }
    $div_inner_html += "</div>"
    $('#div-search-result').html($div_inner_html);
}

// search req
var $ld_in_searching_req = 0;
/**
 * show search result is modal dialog
 */
function ld_show_search_result_in_dialog($type, $data) {
    // check the modal dialog is showing or not
    if ($ld_in_searching == false) {
        console.warn("search modal is hidden when get search result.")
        return;
    }
    // check the search sequence is matched or not
    if ($ld_in_searching_req != $ld_in_searching_seq) {
        console.warn("search modal is not in same seq.")
        return;
    }
    if ($type == 0) {
        ld_set_search_result($data);
        return;
    }
    if ($data.length == 0) {
        ld_set_search_result('No records.');
        return;
    }
    ld_set_search_result_records($data);
}

/**
 * close the search modal dialog
 */
function ld_close_search_dialog() {
    $('#modal-search').modal('hide')
}

var $ld_last_search_records = [];

/**
 * try to show the search result on the exit&entry
 *
 * @param {string} $entry_id the entry record id
 */
function ld_show_search_result($entry_id) {
    var $i = 0
    for ($i = 0; $i < $ld_last_search_records.length; $i++) {
        if ($ld_last_search_records[$i].id == $entry_id) {
            console.log($ld_last_search_records[$i]);
            ld_set_last_exit($ld_last_exit_record, $ld_last_search_records[$i])
            ld_set_matched_entry($ld_last_search_records[$i]);
            ld_set_plate_no_by_search($ld_last_search_records[$i]);
            return;
        }
    }
}

// last change plate no jquery ajax object
var $jq_change_plate = null;

/**
 * send search request to server
 * @param {string} $plate_no the search car plate no
 */
function ld_req_change_plate($plate_no) {
    // cancel the previous one ajax request
    if ($jq_change_plate != null) {
        $jq_change_plate.abort();
        $jq_change_plate = null;l
    }
    // issue request
    $jq_change_plate = $.ajax({
        method: "GET",
        url: "dashboard/search_entry",
        data: { plate_no: $plate_no,limit:1,full_match:1 }
    }).done(function($resp) {
        if ($resp.code==0 && $resp.data.length>=1) {
            $ld_last_exit_record.plate_no=$resp.data[0].plate_no;
            ld_set_last_exit($ld_last_exit_record, $resp.data[0])
            ld_set_matched_entry($resp.data[0]);
            ld_notify('Change plate number succeeded.','success');
        } else {
            ld_notify('No record found. Please try again.','danger');
        }
    }).fail(function($resp) {
        console.log($resp);
        ld_notify('Something wrong. Please try again.','danger');
    }).always(function() {
            $jq_change_plate = null;
    });
}


// last manually leave jquery ajax object
var $jq_manually_leave = null;

/**
 * send manual leave request to server
 * @param {integer} $id the last exit id
 * @param {string} $plate_no the car plate no
 * @param {string} $remark the manual leave remark
 */
function ld_req_manually_leave($id,$plate_no,$remark) {
    // cancel the previous one ajax request
    if ($jq_manually_leave != null) {
        $jq_manually_leave.abort();
        $jq_manually_leave = null;l
    }
    // issue request
    $jq_manually_leave = $.ajax({
        method: "POST",
        url: "dashboard/manual_push_plate_no",
        data: { entry_log_id:$id,plate_no: $plate_no,remark: $remark }
    }).done(function($data) {
        if ($data.code==0) {
            // refresh the last lane since manually leave succeeded.
            ld_req_last_exit($last_lane_id,'');
            ld_notify('Manually leave succeeded','success');
        } else {
            ld_notify($data.message,'danger');
        }
    })
        .fail(function($data) {
            console.log($data);
            ld_notify('Something wrong. Please try again.','danger');
        })
        .always(function() {
            $jq_manually_leave = null;
        });
}
/**
 * button click callback function
 *
 * @param {string} $type button click type
 * @param {string} $id button id
 */
function ld_on_button_clicked($type, $id) {
    if ($type == 'search') {
        $ld_in_searching_req = $ld_in_searching_seq;
        var $search_no = $("#input-search").val()
        if ($search_no != '') {
            ld_req_search_entry($search_no);
        }
        return;
    }
    if ($type == 'result') {
        ld_close_search_dialog();
        ld_show_search_result($id);
        return;
    }
    if ($type == 'change') {
        var $changed_plate_no = $('#input-plate-no').val();
        if ($changed_plate_no!=$ld_last_exit_record.plate_no) {
            ld_req_change_plate($changed_plate_no);
        }
        return;
    }
    if ($type == 'leave') {
        swal({
            title: 'Confirm manually leave?',
            text: 'This operation will open the barrier gateway.',
            type: 'warning',
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonClass: 'btn btn-primary',
            confirmButtonText: 'Confirm',
            cancelButtonClass: 'btn btn-secondary'
        }).then(result => {
            if(result.value){
                var $changed_plate_no = $('#input-plate-no').val();
                var $remark = $('#input-remark').val();''
                ld_req_manually_leave($ld_last_exit_record.id,$changed_plate_no,$remark);
            }
        });
        return;
    }
}

var $last_lane_id = 0;
function ld_on_lane_button_clicked($id, $name) {
    $last_lane_id = $id;
    ld_req_last_exit($id, 'Exit1');
}

// last exit jquery ajax object
var $jq_last_exit = null;
/**
 * send request to server and get last exit record by lane id
 *
 * @param {string} $lane_id the lane id
 * @param {string} $lane_name the lane name
 */
function ld_req_last_exit($lane_id, $lane_name) {
    if ($jq_last_exit != null) {
        $jq_last_exit.abort();
        $jq_last_exit = null;
    }
    $jq_last_exit = $.ajax({
        method: "GET",
        url: "dashboard/last_exit",
        data: { lane_id: $lane_id }
    }).done(function($resp) {
        if ($resp.code==0) {
            var $data = $resp.data;
            // cache the last exit record
            $ld_last_exit_record = $data.exit_record;
            var $matched_entry_record = $data.entry_record;
            if ($matched_entry_record == null) {
                $matched_entry_record = {};
            }
            ld_set_last_exit($ld_last_exit_record, $matched_entry_record)
            ld_set_matched_entry($matched_entry_record);
        } else {
            $ld_last_exit_record = null;
            ld_clear_last_exit($lane_name);
            ld_clear_matched_entry();
        }
    })
        .fail(function($resp) {
            console.log($resp);
            $ld_last_exit_record = null;
            ld_clear_last_exit($lane_name);
            ld_clear_matched_entry();
        })
        .always(function() {
            $jq_last_exit = null;
        });
}

// last search entry jquery ajax object
var $jq_search_entry = null;

/**
 * send search request to server
 * @param {string} $search_no the search car plate no
 */
function ld_req_search_entry($search_no) {
    // cancel the previous one ajax request
    if ($jq_search_entry != null) {
        $jq_search_entry.abort();
        $jq_search_entry = null;
    }
    // show the searching text
    ld_set_search_result('Searching......');
    $ld_last_search_records=[];
    // issue request
    $jq_search_entry = $.ajax({
        method: "GET",
        url: "dashboard/search_entry",
        data: { plate_no: $search_no,limit:10 }
    }).done(function($resp) {
        if ($resp.code==0) {
            var $data = $resp.data;
            $ld_last_search_records = $data;
            ld_show_search_result_in_dialog(1, $data);
        } else {
            ld_show_search_result_in_dialog(0, $resp.message);
        }
    })
        .fail(function($data) {
            ld_show_search_result_in_dialog(0, 'Something wrong. Please try again.');
        })
        .always(function() {
            $jq_search_entry = null;
        });
}
// show the search dialog or not
var $ld_in_searching = false;
// the search sequence number
var $ld_in_searching_seq = 0;
$(function() {
    $('#modal-search').on('hidden.bs.modal', function() {
        $ld_in_searching = false;
        ld_set_search_result('');
    })
    $('#modal-search').on('shown.bs.modal', function() {
        $ld_in_searching = true;
        ld_set_search_result('');
        $ld_in_searching_seq = $ld_in_searching_seq + 1;
    })
})
