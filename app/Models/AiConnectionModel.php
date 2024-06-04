<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AiConnectionModel extends Model
{
    public function getConnectionName()
    {
        $user = Auth::user();
        $departmentId = $user->department_id;

        return 'horusvn_ai';
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
