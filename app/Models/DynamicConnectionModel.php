<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DynamicConnectionModel extends Model
{
    public function getConnectionName()
    {
        // Perform your condition check based on the user's department ID
        $user = Auth::user();
        $departmentId = $user->department_id;

        // if (in_array($user->id, [46,107])) {
        //     // Check if the admin has manually switched the connection
        //     $adminConnection = config('const.productions_connection');

        //     return $adminConnection;
        // } else {
        // if ($departmentId === 12 || in_array($user->id, [90])) {
        if ($departmentId === 12) {
            return 'horusvn_ai';
        } else {
            return 'horusvn_productions';
        }
        // }

        // Return the default connection if no conditions are met
        return parent::getConnectionName();
    }

    public static function performTransaction($callback)
    {
        $model = new static();
        $connectionName = $model->getConnectionName();

        DB::connection($connectionName)->beginTransaction();

        try {
            $result = $callback($model);

            DB::connection($connectionName)->commit();

            return $result;
        } catch (\Exception $e) {
            DB::connection($connectionName)->rollBack();
            throw $e;
        }
    }
}
