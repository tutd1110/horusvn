<?php

namespace App\Http\Controllers\api\OrderStore;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderStore;
use Illuminate\Support\Facades\Log;
use \Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\api\OrderStore\SaveOrderStoreRequest;
use App\Models\OrderSetting;
use Illuminate\Support\Facades\DB;

/**
 * OrderStore API
 *
 * @group OrderStore
 */
class OrderStoreMenuController extends Controller
{
    /**
     * @var number
     */
    protected $limit = 12;

    /**
     * @var string
     */
    protected $sort = 'updated_at';

    /**
     * @var string
     */
    protected $dir = 'DESC';

    public function __construct()
    {
    }

    public function getListMenu(Request $request)
    {
        try {
            $this->limit = request()->query('size') ?? $this->limit;
            $this->sort = request()->query('sort') ?? $this->sort;
            $this->dir = request()->query('dir') ?? $this->dir;

            $collection = OrderStore::with(['menu'])
                ->orderBy($this->sort, $this->dir)
                ->paginate($this->limit);

            return response()->json(['order_store' => $collection]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => __('MSG-E-003')
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
