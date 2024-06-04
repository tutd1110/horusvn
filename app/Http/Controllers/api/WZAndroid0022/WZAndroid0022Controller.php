<?php

namespace App\Http\Controllers\api\WZAndroid0022;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Repositories\WZAndroid0022Repository;
use App\Http\Requests\api\WZAndroid0022\TrackingInOutRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Cache;

/**
 * WZAndroid0022 API
 *
 * @group WZAndroid0022
 */
class WZAndroid0022Controller extends Controller
{
    /**
     * @var WZAndroid0022Repository
     */
    private $wzAndroid0022Repository;

    protected $cacheIncome = 'wzandroid0022_data_income';
    protected $cacheOutcome = 'wzandroid0022_data_outcome';
    protected $wzandroid0022 = 'wzandroid0022';
    protected $cacheApi = 'url_api';
    protected $cacheFileName = 'in/outcome_file';

    public function __construct(WZAndroid0022Repository $wzAndroid0022Repository)
    {
        $this->wzAndroid0022Repository = $wzAndroid0022Repository;
    }

    public function getSelboxes()
    {
        try {
            $apis = Cache::get($this->cacheApi);

            $cacheKey = Auth()->user()->id.'_'.$this->wzandroid0022;
            $cachedData = Cache::get($cacheKey);
            $filters = isset($cachedData['filters']) ? $cachedData['filters'] : null;
            $cacheFileName = Cache::get($this->cacheFileName);
            $fileName = isset($cacheFileName['filename']) ? $cacheFileName['filename'] : null;

            $data = [
                'api_selbox' => $apis,
                'filters' => $filters,
                'file_name' => $fileName
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getTrackingInOut(TrackingInOutRequest $request)
    {
        try {
            set_time_limit(300);

            $cacheKey = Auth()->user()->id.'_'.$this->wzandroid0022;
            Cache::forget($cacheKey);

            //on request
            $requestDatas = $request->all();

            $formattedFrom = "";
            if (isset($requestDatas['start_time'])) {
                $from = new Carbon($requestDatas['start_time']." 07:00:00");
                $formattedFrom = $from->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i:sP');
            }

            $formattedTo = "";
            if (isset($requestDatas['end_time'])) {
                $to = new Carbon($requestDatas['end_time']." 07:00:00");
                $formattedTo = $to->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i:sP');
            }

            $checkCompare = "";
            if (isset($requestDatas['check_compare'])) {
                $checkCompare = $requestDatas['check_compare'] > 0 ? true : false;
            }

            $checkTypeStr = "Gold";
            if (strval($requestDatas['check_type']) === "2") {
                $checkTypeStr = "Cash";
            }

            $typeStr = "In";
            $keyCache = $this->cacheIncome;
            if (strval($requestDatas['type']) === "1") {
                $typeStr = "Out";
                $keyCache = $this->cacheOutcome;
            }

            $objectStr = $checkTypeStr."Data".$typeStr;

            $details = Cache::get($keyCache);
            if (!$details) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => "Bạn chưa import danh sách chi tiết"
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $res = $this->wzAndroid0022Repository->getTrackingInOut(
                $requestDatas['api'],
                $formattedFrom,
                $formattedTo,
                isset($requestDatas['day']) ? $requestDatas['day'] : "",
                isset($requestDatas['check_type']) ? $requestDatas['check_type'] : "",
                isset($requestDatas['user_type']) ? $requestDatas['user_type'] : "",
                isset($requestDatas['version']) ? $requestDatas['version'] : "",
                $checkCompare
            );

            $logs = $res->Data;
            if (empty((array)$logs->$objectStr)) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = array_merge(...array_values(get_object_vars($logs->$objectStr)));

            $transformedData = collect($data)
            ->map(function ($item) {
                $result = [];

                if ($item->Method === "ALL") {
                    $result = [
                        [
                            'Method' => $item->Method,
                            'Name' => 'ALL',
                            'Group' => 'ALL',
                            'GroupDetail1' => 'ALL',
                            'GroupDetail2' => 'ALL',
                            'Total' => $item->Total,
                            'User' => $item->User,
                            'AVG' => $item->AVG,
                        ]
                    ];
                }

                if (isset($item->Detail) && is_array($item->Detail)) {
                    $result = array_merge($result, collect($item->Detail)->map(function ($detail) use ($item) {
                        return [
                            'Method' => $item->Method,
                            'ItemId' => $detail->ItemId,
                            'Total' => $detail->Total,
                            'User' => $detail->User,
                            'AVG' => $detail->AVG,
                        ];
                    })->toArray());
                }

                return $result;
            })
            ->flatten(1);

            $collectDetails = collect($details);

            $mergedArray = $transformedData->map(function ($item) use ($collectDetails) {
                $mergedItem = $item;

                if (isset($item['ItemId'])) {
                    $matchedItem = $collectDetails->where('id', $item['ItemId'])
                                            ->where('method', $item['Method'])
                                            ->first();

                    if ($matchedItem) {
                        $mergedItem['Name'] = $matchedItem['name'];
                        $mergedItem['Group'] = $matchedItem['group'];
                        $mergedItem['GroupDetail1'] = $matchedItem['group_detail_1'];
                        $mergedItem['GroupDetail2'] = $matchedItem['group_detail_2'];
                    } else {
                        $mergedItem['Name'] = 'N/A';
                        $mergedItem['Group'] = 'N/A';
                        $mergedItem['GroupDetail1'] = 'N/A';
                        $mergedItem['GroupDetail2'] = 'N/A';
                    }
                }
                return $mergedItem;
            });

            $sumTotal = $mergedArray->where('Method', '!=', 'ALL')->sum('Total');
            $sumUser = $mergedArray->where('Method', '!=', 'ALL')->sum('User');
            $summary = $mergedArray->map(function ($item) use ($sumTotal, $sumUser) {
                $item['PercentTotal'] = $item['Total'] / $sumTotal * 100;
                $item['PercentUser'] = $item['User'] / $sumUser * 100;

                $item['AVG'] = round($item['AVG'], 2);

                return $item;
            });

            $allItems = $summary->where('Method', 'ALL');
            $summary = $summary->where('Method', '!=', 'ALL');
            $summary->prepend([
                'Method' => 'TOTAL',
                'Name' => 'TOTAL',
                'Group' => 'TOTAL',
                'GroupDetail1' => 'TOTAL',
                'GroupDetail2' => 'TOTAL',
                'Total' => $sumTotal,
                'User' => $sumUser,
                'PercentTotal' => $summary->sum('PercentTotal'),
                'PercentUser' => $summary->sum('PercentUser'),
                'AVG' => $sumTotal/$sumUser,
            ]);
            $summary->prepend($allItems->all()[0]);

            // Round PercentTotal and PercentUser
            $summary = $summary->map(function ($item) {
                $item['PercentTotal'] = round($item['PercentTotal'], 2);
                $item['PercentUser'] = round($item['PercentUser'], 2);
                $item['AVG'] = round($item['AVG'], 2);
                return $item;
            });

            //store summary to cache
            $data = [
                'filters' => [
                    'api' => $requestDatas['api'],
                    'type' => $requestDatas['type'],
                    'start_time' => isset($requestDatas['start_time']) ?
                        Carbon::create($requestDatas['start_time'])->format('d/m/Y') : null,
                    'end_time' => isset($requestDatas['end_time']) ?
                        Carbon::create($requestDatas['end_time'])->format('d/m/Y') : null,
                    'day' => $requestDatas['day'],
                    'check_type' => $requestDatas['check_type'],
                    'user_type' => $requestDatas['user_type'],
                    'check_compare' => $requestDatas['check_compare'],
                    'version' => $requestDatas['version']
                ],
                'summary' => $summary
            ];
            Cache::forever($cacheKey, $data);

            return response()->json($summary);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getGroup(Request $request)
    {
        try {
            set_time_limit(300);

            //on request
            $requestDatas = $request->all();

            $cacheKey = Auth()->user()->id.'_'.$this->wzandroid0022;
            $cachedData = Cache::get($cacheKey);
            $summary = isset($cachedData['summary']) ? $cachedData['summary'] : collect([]);

            if ($summary->isEmpty()) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            if (isset($requestDatas['group_by']) && count($requestDatas['group_by']) > 0) {
                $groupBy = $requestDatas['group_by'];
                $groupedData = [];
            
                foreach ($summary as $item) {
                    $groupKey = $this->getGroupKey($item, $groupBy);

                    if (!isset($groupedData[$groupKey])) {
                        $groupedData[$groupKey] = [
                            'Total' => $item['Total'],
                            'User' => $item['User'],
                            'PercentTotal' => $item['PercentTotal'],
                            'PercentUser' => $item['PercentUser'],
                            'AVG' => $item['AVG'],
                        ];
                    
                        foreach ($groupBy as $key) {
                            $groupedData[$groupKey][$key] = $item[$key];
                        }
                    } else {
                        $groupedData[$groupKey]['Total'] += $item['Total'];
                        $groupedData[$groupKey]['User'] += $item['User'];
                        $groupedData[$groupKey]['PercentTotal'] += $item['PercentTotal'];
                        $groupedData[$groupKey]['PercentUser'] += $item['PercentUser'];
                        $groupedData[$groupKey]['AVG'] += $item['AVG'];
                    }
                }
            
                $summary = collect(array_values($groupedData));
            }

            // Round PercentTotal and PercentUser
            $summary = collect($summary)->map(function ($item) {
                $item['PercentTotal'] = round($item['PercentTotal'], 2);
                $item['PercentUser'] = round($item['PercentUser'], 2);
                $item['AVG'] = round($item['AVG'], 2);
                return $item;
            });

            return response()->json($summary);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getGroupKey($item, $groupBy)
    {
        $groupKey = '';
        foreach ($groupBy as $key) {
            $groupKey .= $item[$key];
        }
        return $groupKey;
    }
}
