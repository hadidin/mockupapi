<?php

namespace App;

class Permission extends \Spatie\Permission\Models\Permission
{
    public static function defaultPermissions()
    {

        return [
            'view_users|Ability to view all user records at "Users" page',
            'add_users|Ability to add new user records at "Users" page',
            'edit_users|Ability to edit user records at "Users" page',
            'delete_users|Ability to delete user records at "Users" page',
            'view_roles|Ability to view all role records at "Roles" page',
            'add_roles|Ability to view all role records at "Roles" page',
            'edit_roles|Ability to view all role records at "Roles" page',
            'delete_roles|Ability to view all role records at "Roles" page',
            'view_car_in_site|Ability to view all car in-site records at "Car In-Site" page',
            'view_entry_log_db|Ability to view all entry log records at "Entry Log" page',
            'view_entry_log_reviewed|Ability to view all entry log reviewed records at "Entry Log Reviewed" page',
            'view_entry_history|Ability to view all season parking records at "Season Parking" page',
	    'view_lane_config|Ability to view all lane/camera records at "Lane/Camera Info" page',
	    'view_audit|Ability to view all audit logs',
            'view_logs|Ability to view logs',
            'view_plate_no_map|Ability to view plate no map',
            'create_plate_no_map|Ability to add plate no map',
            'edit_plate_no_map|Ability to edit plate no map',
            'delete_plate_no_map|Ability to delete plate no map',
            'view_white_list|Ability to view white list',
            'create_white_list|Ability to create white list',
            'edit_white_list|Ability to edit white list',
            'delete_white_list|Ability to delete white list',
            'view_season|Ability to view season account',
            'create_season|Ability to create season account',
            'edit_season|Ability to edit season account',
            'delete_season|Ability to delete season account',
        ];
    }
}
