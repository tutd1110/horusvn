<?php

namespace App\Http\Controllers\api\Review;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Review;
use App\Models\ContentReview;
use App\Models\User;
use App\Models\Mentee;
use App\Models\QuestionReview;
use App\Models\EmployeeReviewPoint;
use App\Models\EmployeeAnswer;
use App\Models\ReviewComment;
use App\Http\Requests\api\Review\ReviewEditRequest;
use App\Http\Requests\api\Review\ReviewRegisterRequest;
use App\Http\Requests\api\Review\SendReviewRequest;
use Carbon\Carbon;

/**
 * Review API
 *
 * @group Review
 */
class ReviewController extends Controller
{
    public function loadReviewData(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $data = CommonController::loadReviewData($requestDatas);

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(ReviewRegisterRequest $request)
    {
        $requestDatas = $request->all();

        //only HR can use this action
        try {
            $content = ContentReview::select('id')->get();

            $employee = User::findOrFail($requestDatas['employee_id']);
            $leader = null;
            $mentor = null;
            if ($employee->position == 0) {
                $leader = User::select('id')->where('department_id', $employee->department_id)
                        ->where('position', 1)
                        ->first();

                if (in_array($requestDatas['period'], [0,1])) {
                    $mentor = Mentee::select('mentor_id as id')->where('mentee_id', $employee->id)->first();
                }
            }
            $pm = User::select('id')->where('position', 2)->first();

            //start transaction
            DB::beginTransaction();

            //detect type of review base on start date employee join
            $questionType = 0;
            if ($requestDatas['period'] == 2) {
                $questionType = 1;
            }

            $questions = QuestionReview::where('period', $questionType)->get();

            //insert review statuses
            $review = Review::create([
                'start_date' => Carbon::createFromFormat('d/m/Y', $requestDatas['start_date'])->format('Y/m/d'),
                'employee_id' => $employee->id,
                'period' => $requestDatas['period'],
                'progress' => 4,
            ]);

            $isDirectorReviewees = CommonController::isDirectorReviewees($employee);
            $employeePointData = [];
            foreach ($content as $item) {
                $reviewPoint = [
                    'review_id' => $review->id,
                    'content_review_id' => $item->id,
                    'employee_id' => $employee->id,
                    'leader_id' => $leader ? $leader->id : null,
                    'pm_id' => $pm->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                if ($questionType == 0) {
                    $reviewPoint['mentor_id'] = $mentor ? $mentor->id : null;
                }
                
                if ($isDirectorReviewees) {
                    unset($reviewPoint['mentor_id'], $reviewPoint['leader_id'], $reviewPoint['pm_id']);
                }
            
                $employeePointData[] = $reviewPoint;
            }
            //insert employee review points
            EmployeeReviewPoint::insert($employeePointData);

            $this->storeEmployeeAnswers(
                $review,
                $questions,
                $isDirectorReviewees,
                $employee,
                $mentor,
                $leader,
                $pm
            );

            //insert review for pm or director
            ReviewComment::create([
                'review_id' => $review->id
            ]);

            DB::commit();

            return response()->json($review->id);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function sendReview(SendReviewRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            //check if there is review that's exist
            $review = Review::where('employee_id', $requestDatas['employee_id'])->where('progress', 0)->first();
            if ($review) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-017')
                ], Response::HTTP_NOT_FOUND);
            }

            $content = ContentReview::select('id')->get();

            $employee = User::findOrFail($requestDatas['employee_id']);
            $mentor = null;
            $leader = null;
            if ($employee->position == 0) {
                $leader = User::select('id')->where('department_id', $employee->department_id)->where('position', 1)
                        ->first();

                if (in_array($requestDatas['period'], [0,1,4])) {
                    $mentor = Mentee::select('mentor_id as id')->where('mentee_id', $employee->id)->first();
                }
            }

            $pm = User::select('id')->where('position', 2)->first();

            //start transaction
            DB::beginTransaction();

            //detect type of review base on start date employee join
            $startDate = Carbon::parse($employee->created_at);
            $questionType = 0;//2 months
            if ($requestDatas['period'] == 2) {
                $questionType = 1; //6 months
            }
            if ($requestDatas['period'] == 3) {
                $questionType = 3; //1 year
            }
            if ($requestDatas['period'] == 4) {
                $questionType = 2; //hoc viec
            }
            $questions = QuestionReview::where('period', $questionType)->get();

            //insert review statuses
            $review = Review::create([
                'start_date' => Carbon::now()->format('Y/m/d'),
                'employee_id' => $employee->id,
                'period' => $requestDatas['period'],
                'progress' => $employee->position,
            ]);

            $isDirectorReviewees = CommonController::isDirectorReviewees($employee);
            $employeePointData = [];
            foreach ($content as $item) {
                $reviewPoint = [
                    'review_id' => $review->id,
                    'content_review_id' => $item->id,
                    'employee_id' => $employee->id,
                    'leader_id' => $leader ? $leader->id : null,
                    'pm_id' => $pm->id,
                ];

                if ($questionType == 0) {
                    $reviewPoint['mentor_id'] = $mentor ? $mentor->id : null;
                }
                
                if ($isDirectorReviewees) {
                    unset($reviewPoint['mentor_id'], $reviewPoint['leader_id'], $reviewPoint['pm_id']);
                }
            
                $employeePointData[] = $reviewPoint;
            }
            EmployeeReviewPoint::insert($employeePointData);

            $this->storeEmployeeAnswers(
                $review,
                $questions,
                $isDirectorReviewees,
                $employee,
                $mentor,
                $leader,
                $pm
            );

            //insert review for pm or director
            ReviewComment::create([
                'review_id' => $review->id
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-003'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(ReviewEditRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $review = Review::findOrFail($requestDatas['id']);
            //start transaction
            DB::beginTransaction();

            if (array_key_exists('start_date', $requestDatas)) {
                $review->start_date = $requestDatas['start_date'];
            }
            if (array_key_exists('next_date', $requestDatas)) {
                $review->next_date = $requestDatas['next_date'];
            }

            $review->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function storeEmployeeAnswers(
        $review,
        $questions,
        $isDirectorReviewees,
        $employee,
        $mentor,
        $leader,
        $pm
    ) {
        $groupEmployees = [];
        if (!$isDirectorReviewees) {
            $groupEmployees = array_values(array_filter(
                [
                    $mentor ? ['id' => $mentor->id, 'type' => 0.5] : null,
                    $leader ? ['id' => $leader->id, 'type' => 1] : null,
                    ['id' => $pm->id, 'type' => 2]
                ],
                function ($item) {
                    return !is_null($item);
                }
            ));
        }

        $employeeAnswers = [];
        $questions->each(function ($question) use ($review, $employee, $groupEmployees, &$employeeAnswers) {
            if ($question->type == 0) {
                $employeeAnswers[] = [
                    'review_id' => $review->id,
                    'question_review_id' => $question->id,
                    'employee_id' => $employee->id,
                    'type' => $employee->position
                ];
            } else {
                foreach ($groupEmployees as $employee) {
                    $employeeAnswers[] = [
                        'review_id' => $review->id,
                        'question_review_id' => $question->id,
                        'employee_id' => $employee['id'],
                        'type' => $employee['type']
                    ];
                }
            }
        });
        //insert employee answer question review
        EmployeeAnswer::insert($employeeAnswers);
    }
}
