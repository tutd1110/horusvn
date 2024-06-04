<?php

namespace App\Http\Controllers\api\Tracking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Petition API
 *
 * @group Petition
 */
class TrackingGameController extends Controller
{
    public function getTrackingGame(Request $request)
    {
        try {
            $trackingGame = config('const.tracking_game');
            $data = $trackingGame;

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

   
}
