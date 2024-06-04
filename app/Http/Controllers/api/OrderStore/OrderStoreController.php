<?php

namespace App\Http\Controllers\api\OrderStore;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderStore;
use Illuminate\Support\Facades\Log;
use \Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\api\OrderStore\SaveOrderStoreRequest;
use App\Models\OrderStoreMenu;
use Illuminate\Support\Facades\DB;

/**
 * OrderStore API
 *
 * @group OrderStore
 */
class OrderStoreController extends Controller
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

    public function getListOrderStore(Request $request)
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

    public function show($id)
    {
        try {
            $store = OrderStore::select('id', 'name', 'phone', 'location', 'type', 'price','max_item')
                ->with(['menu'])->where('id', $id)->first();

            return response()->json(['order_store' => $store]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => __('MSG-E-003')
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(SaveOrderStoreRequest $request)
    {
        try {
            DB::beginTransaction();
           
            $model = new OrderStore();
            $model->fill($request->all());
            $model->save();

            $model->menu()->createMany($request->menu);

            DB::commit();
            return response()->json(['store' => $model, 'message' => "Tạo cửa hàng thành công"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            throw $th;
        }
    }

    public function update(SaveOrderStoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();
           
            $model = OrderStore::find($id);
            $model->fill($request->all());
            $model->save();

            $model->menu()->forceDelete();
            $model->menu()->createMany($request->menu);
            DB::commit();
            return response()->json(['store' => $model, 'message' => "Cập nhật cửa hàng thành công"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            throw $th;
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            OrderStore::destroy($id);
            DB::commit();
            return response()->json(['message' => "Xóa cửa hàng thành công"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            throw $th;
        }
    }
}
