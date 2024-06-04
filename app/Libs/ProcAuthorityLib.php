<?php

namespace App\Libs;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Library for permission checking for functions
 */
class ProcAuthorityLib
{
    /**
     * Save log user login
     *
     *  @param $procId Target function ID
     *  @param $user User login
    */
    public function checkAuthority(String $procId, Object $user)
    {
        // Log::info($procId . ':' . $user->id);
        //employees role
        $pmIdsRole = config('const.employee_id_pm_roles');
        $addPermission = config('const.employee_add_permission');

        // if (in_array($procId, ['tasks', 'projects'])) {
        //     //only admin, manager or director can access this screen
        //     if (!in_array($user->id, $pmIdsRole) && !in_array($user->id, $addPermission)) {
        //         abort(Response::HTTP_FORBIDDEN);
        //     }
        // }
        // 
        // if ($procId == 'tasks') {
        //     //only admin, manager or director can access this screen
        //     if (!in_array($user->id, $pmIdsRole) && !in_array($user->id, $addPermission)) {
        //         abort(Response::HTTP_FORBIDDEN);
        //     }
        // }

        if ($procId == 'projects') {
            //only admin, manager or director can access this screen
            if (!in_array($user->id, $pmIdsRole)) {
                abort(Response::HTTP_FORBIDDEN);
            }
        }
    }
}
