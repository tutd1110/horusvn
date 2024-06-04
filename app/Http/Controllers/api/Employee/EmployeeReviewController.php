<?php

namespace App\Http\Controllers\api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Mentee;
use App\Models\Review;
use App\Models\ReviewFile;
use App\Models\EmployeeReviewPoint;
use App\Models\QuestionReview;
use App\Models\EmployeeAnswer;
use App\Models\ContentReview;
use App\Models\ReviewComment;
use App\Http\Requests\api\EmployeeReview\EmployeeReviewPointEditRequest;
use App\Http\Requests\api\EmployeeReview\EmployeeAnswerEditRequest;
use App\Http\Requests\api\EmployeeReview\ReviewEditRequest;
use App\Http\Requests\api\EmployeeReview\ReviewDeleteRequest;
use App\Http\Requests\api\EmployeeReview\ReviewFileStoreRequest;
use App\Http\Requests\api\EmployeeReview\ReviewFileDeleteRequest;
use Carbon\Carbon;
use File;

/**
 * Employee Review API
 *
 * @group Employee Review
 */
class EmployeeReviewController extends Controller
{
    public function getEmployeeListWithReview(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $employees = $this->sqlRawGetEmployees($requestDatas);

            return response()->json($this->transferEmployeeData($employees));
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployees()
    {
        try {
            $employeeLogin = Auth()->user();

            $menteeIds = [];
            if ($employeeLogin->position == 0) {
                $menteeIds = Mentee::where('mentor_id', $employeeLogin->id)->pluck('mentee_id');
            }

            $progress = 0;
            if ($employeeLogin->position == 0 && count($menteeIds) > 0) {
                $progress = 0.5;
            } elseif ($employeeLogin->position == 1) {
                $progress = 1;
            } elseif ($employeeLogin->position == 2) {
                $progress = 2;
            } elseif ($employeeLogin->position == 3) {
                $progress = 3;
            } elseif ($employeeLogin->department_id == 7) {
                $progress = 4;
            }

            $employees = Review::join('users', function ($join) {
                $join->on('users.id', '=', 'reviews.employee_id')
                    ->whereNull('reviews.deleted_at');
            })
            ->select('users.id as id', 'users.fullname as fullname')
            ->where(function ($query) use ($employeeLogin, $progress, $menteeIds) {
                $query->where('reviews.progress', $progress)
                        ->where('reviews.employee_id', '!=', $employeeLogin->id);

                if ($employeeLogin->position == 1) {
                    $query->where('users.department_id', $employeeLogin->department_id);
                }

                if (count($menteeIds) > 0) {
                    $query->whereIn('users.id', $menteeIds);
                }
            })
            ->get();

            $data = [
                'employee_position' => count($menteeIds) > 0 ? 0.5 : $employeeLogin->position,
                'employees' => $employees
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getReviews(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            //review period
            $reviewPeriod = config('const.review_period');

            $employeeLogin = Auth()->user();

            $employeeId = $employeeLogin->id;
            $employeeDepartmentId = $employeeLogin->department_id;
            $progress = $employeeLogin->position;

            $reviewee = null;
            $mentor = null;
            if (isset($requestDatas['employee_id']) && !empty($requestDatas['employee_id'])) {
                $mentor = Mentee::join('users', 'users.id', '=', 'mentees.mentor_id')
                    ->select(
                        'users.id as id',
                        'users.fullname as fullname'
                    )
                    ->where('mentee_id', $requestDatas['employee_id'])
                    ->first();
                if ($mentor && $mentor->id === $employeeLogin->id && $employeeLogin->position != 1) {
                    $progress = 0.5;
                }

                if ($progress >= 0.5) {
                    //departments
                    $departments = config('const.departments');
                    //positions
                    $positions = config('const.positions');

                    $employeeId = $requestDatas['employee_id'];

                    $reviewee = User::leftJoin('reviews', function ($join) {
                        $join->on('reviews.employee_id', '=', 'users.id')->whereNull('reviews.deleted_at')
                                ->where('reviews.progress', 4);
                    })
                    ->select(
                        'users.id as id',
                        'users.date_official as date_official',
                        DB::raw("max(reviews.start_date) as last_review"),
                        'users.fullname as fullname',
                        'users.position as position',
                        'users.department_id as department_id',
                        'users.created_at as created_at'
                    )
                    ->where('users.id', $employeeId)
                    ->groupBy('users.id')
                    ->first();

                    $reviewee->department_name = isset($departments[$reviewee->department_id])
                        ? $departments[$reviewee->department_id] : "";
                    $reviewee->position_name = isset($positions[$reviewee->position])
                        ? $positions[$reviewee->position] : "";

                    $employeeDepartmentId = $reviewee->department_id;
                }
            }

            $isDirectorReviewees = CommonController::isDirectorReviewees($employeeLogin);
            //get review
            $review = Review::select('id', 'period', 'start_date')
                ->where('employee_id', $employeeId)
                ->where('progress', $progress)
                ->first();

            if (!$review) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => __('MSG-E-003')
                    ], Response::HTTP_NOT_FOUND);
            }

            $review->name = $reviewPeriod[$review->period];

            //get content review
            $content = EmployeeReviewPoint::join('content_reviews', function ($join) {
                $join->on('content_reviews.id', '=', 'employee_review_points.content_review_id')
                    ->whereNull('content_reviews.deleted_at');
            })
            ->selectRaw(DB::raw("
                employee_review_points.id as id,
                content_reviews.content as content,
                employee_review_points.employee_point as employee_point,
                employee_review_points.mentor_point as mentor_point,
                employee_review_points.leader_point as leader_point,
                employee_review_points.pm_point as pm_point"))
            ->where('employee_review_points.review_id', $review->id)
            ->orderBy('content_reviews.id', 'asc')
            ->get();

            //get review questions and answers
            $questions = $this->getReviewQuestionAnswers($review->id, $employeeLogin, $mentor);
            $questions->each(function ($question) {
                $question->type = $question->type >= 1 ? intval($question->type) : round($question->type, 1);
            });
            //get group employee for the review
            $groups = $questions->groupBy('fullname');
            $employees = $groups->map(function ($question) {
                return [
                    'id' => $question->first()->employee_id,
                    'fullname' => $question->first()->fullname,
                    'position' => $question->first()->type
                ];
            })->values()->all();

            $comments = [];
            if ($employeeLogin->position > 2) {
                $comments = ReviewComment::join('reviews', function ($join) {
                    $join->on('reviews.id', '=', 'review_comments.review_id')
                        ->whereNull('reviews.deleted_at');
                })
                ->select(
                    'review_comments.id as id',
                    'review_comments.pm_comment as pm_comment',
                    'review_comments.director_comment as director_comment',
                    'review_comments.status as status'
                )
                ->where('review_comments.review_id', $review->id)
                ->first();
            }

            $data = [
                'review' => $review,
                'content' => $content,
                'questions' => $questions,
                'employees' => $employees,
                'comment' => $comments
            ];

            if ($reviewee) {
                $data['reviewee'] = $reviewee;
            }

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getReviewQuestionAnswers($reviewId, $employeeLogin, $mentor)
    {
        $questions = [];
        //get review questions
        $questions = EmployeeAnswer::join('question_reviews', function ($join) {
            $join->on('question_reviews.id', '=', 'employee_answers.question_review_id')
                ->whereNull('question_reviews.deleted_at');
        })
        ->join('users', function ($join) {
            $join->on('users.id', '=', 'employee_answers.employee_id')
                ->whereNull('users.deleted_at');
        })
        ->select(
            'employee_answers.id as id',
            'employee_answers.employee_id as employee_id',
            'question_reviews.question as question',
            'question_reviews.id as question_id',
            'users.fullname as fullname',
            'employee_answers.employee_answer as employee_answer',
            'employee_answers.type as type'
        )
        ->where('employee_answers.review_id', $reviewId)
        ->where(function ($query) use ($employeeLogin, $mentor) {
            if ($employeeLogin->position == 0) {
                if (!$mentor) {
                    $query->where('employee_answers.type', 0);
                } else {
                    $query->whereIn('employee_answers.type', [0,0.5]);
                }
            } elseif ($employeeLogin->position == 1) {
                $query->whereIn('employee_answers.type', [0,0.5,1]);
            }
        })
        ->orderByRaw('users.position asc, employee_answers.type asc, question_reviews.id asc')
        ->get();

        //get files
        $files = ReviewFile::select('id', 'file_path', 'employee_answer_id')
            ->where('review_id', $reviewId)->get();

        //merge all files by question review id
        foreach ($questions as $question) {
            $questionFiles = [];
        
            foreach ($files as $file) {
                if ($file->employee_answer_id === $question->id) {
                    $questionFiles[] = $file;
                }
            }
        
            $question->files = $questionFiles;
        }

        return $questions;
    }

    public function getNote(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $review = Review::select(
                'reviews.note as note'
            )
            ->where('reviews.id', $requestDatas['review_id'])
            ->first();

            return response()->json($review);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function updateNote(Request $request)
    {
        try {
            //on requests
            $requestDatas = $request->all();

            $review = Review::findOrFail($requestDatas['id']);

            $review->note = $requestDatas['note'];
            $review->save();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function addMentor(Request $request)
    {
        try {
            //on requests
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();

            $mentor = Mentee::where('mentor_id', $requestDatas['mentor_id'])
                ->where('mentee_id', $requestDatas['employee_id'])
                ->get();
            if (count($mentor) === 0) {
                //insert mentor
                Mentee::create([
                    'mentor_id' => $requestDatas['mentor_id'],
                    'mentee_id' => $requestDatas['employee_id']
                ]);
            }

            $review = Review::findOrFail($requestDatas['review_id']);
            //insert employee_review_points
            $points = EmployeeReviewPoint::where('review_id', $review->id)->get();
            foreach ($points as $point) {
                $point->mentor_id = $requestDatas['mentor_id'];
                $point->save();
            }

            //insert question_reviews
            $period = 0;
            //let check period, if the review period is 6 months, the period will be assigned as 1.
            if ($review->period === 2) {
                $period = 1;
            }
            $questions = QuestionReview::where('period', $period)->where('type', 1)->get();
            foreach ($questions as $question) {
                // Skip if question ID is 13, the mentor does not need to answer the question about salary
                if ($question->id === 23) {
                    continue;
                }
                //insert employee answer question review
                EmployeeAnswer::create([
                    'review_id' => $review->id,
                    'question_review_id' => $question->id,
                    'employee_id' => $requestDatas['mentor_id'], //Mentor
                    'type' => 0.5 //mentor
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function addLeader(Request $request)
    {
        try {
            //on requests
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();
            
            $review = Review::findOrFail($requestDatas['review_id']);
            //insert employee_review_points
            $points = EmployeeReviewPoint::where('review_id', $review->id)->get();
            foreach ($points as $point) {
                $point->leader_id = $requestDatas['leader_id'];
                $point->save();
            }

            //insert question_reviews
            $period = 0;
            //let check period, if the review period is 6 months, the period will be assigned as 1.
            if ($review->period === 2) {
                $period = 1;
            }
            if ($review->period === 4) {
                $period = 2;
            }
            $questions = QuestionReview::where('period', $period)->where('type', 1)->get();
            foreach ($questions as $question) {
                // Skip if question ID is 13, the mentor does not need to answer the question about salary
                if ($question->id === 23) {
                    continue;
                }
                //insert employee answer question review
                EmployeeAnswer::create([
                    'review_id' => $review->id,
                    'question_review_id' => $question->id,
                    'employee_id' => $requestDatas['leader_id'], //leader
                    'type' => 1 //leader
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }
    public function addPM(Request $request)
    {
        try {
            //on requests
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();

            $review = Review::findOrFail($requestDatas['review_id']);
            //insert employee_review_points
            $points = EmployeeReviewPoint::where('review_id', $review->id)->get();
            foreach ($points as $point) {
                $point->pm_id = $requestDatas['pm_id'];
                $point->save();
            }

            //insert question_reviews
            $period = 0;
            //let check period, if the review period is 6 months, the period will be assigned as 1.
            if ($review->period === 2) {
                $period = 1;
            }
            if ($review->period === 4) {
                $period = 2;
            }
            $questions = QuestionReview::where('period', $period)->where('type', 1)->get();
            foreach ($questions as $question) {
                // Skip if question ID is 13, the mentor does not need to answer the question about salary
                if ($question->id === 23) {
                    continue;
                }
                //insert employee answer question review
                EmployeeAnswer::create([
                    'review_id' => $review->id,
                    'question_review_id' => $question->id,
                    'employee_id' => $requestDatas['pm_id'], //pm
                    'type' => 2 //pm
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getReviewsByEmployeeId(Request $request)
    {
        try {
            //Role check
            $employeeScreenRole = config('const.employee_screen_role');
            if (!in_array(Auth()->user()->id, $employeeScreenRole)) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $requestDatas = $request->all();

            $reviews = Review::leftJoin('review_comments', function ($join) {
                $join->on('review_comments.review_id', '=', 'reviews.id')
                    ->whereNull('review_comments.deleted_at');
            })
            ->join('users', 'users.id', '=', 'reviews.employee_id')
            ->select(
                'reviews.id as id',
                'users.fullname as fullname',
                'reviews.progress as progress',
                'reviews.start_date as start_date',
                'reviews.next_date as next_date',
                'reviews.period as period',
                'review_comments.status as status'
            )
            ->where('reviews.employee_id', $requestDatas['employee_id'])
            ->orderBy('reviews.id','desc')
            ->get();

            return response()->json($this->transferReviewsData($reviews));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(ReviewDeleteRequest $request)
    {
        try {
            $requestDatas = $request->all();
            $reviewId = $requestDatas['review_id'];

            $review = Review::findOrFail($reviewId);

            //start transaction
            DB::beginTransaction();

            // Delete review with the given id
            $review->delete();
            // Delete employee review point with the given id
            EmployeeReviewPoint::where('review_id', $reviewId)->delete();
            // Delete employee answer with the given id
            EmployeeAnswer::where('review_id', $reviewId)->delete();
            // Delete review comment with the given id
            ReviewComment::where('review_id', $reviewId)->delete();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(EmployeeReviewPointEditRequest $request)
    {
        $requestDatas = $request->all();

        try {
            $record = EmployeeReviewPoint::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //insert employee review point
            if (array_key_exists('employee_point', $requestDatas)) {
                $record->employee_point = $requestDatas['employee_point'];
            }

            if (array_key_exists('mentor_point', $requestDatas)) {
                $record->mentor_point = $requestDatas['mentor_point'];
            }

            if (array_key_exists('leader_point', $requestDatas)) {
                $record->leader_point = $requestDatas['leader_point'];
            }

            if (array_key_exists('pm_point', $requestDatas)) {
                $record->pm_point = $requestDatas['pm_point'];
            }

            $record->save();

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

    public function employeeAnswers(EmployeeAnswerEditRequest $request)
    {
        $requestDatas = $request->all();

        try {
            $answer = EmployeeAnswer::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            $answer->employee_answer = $requestDatas['answer'];

            $answer->save();

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

    public function comment(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $comment = ReviewComment::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            if (array_key_exists('status', $requestDatas)) {
                $comment->status = $requestDatas['status'];
            }

            if (array_key_exists('director_comment', $requestDatas)) {
                $comment->director_comment = $requestDatas['director_comment'];
            }

            $comment->save();

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

    public function submit(ReviewEditRequest $request)
    {
        try {
            $requestDatas = $request->all();

            $review = Review::findOrFail($requestDatas['id']);

            $employeeLogin = Auth()->user();

            $isDirectorReviewees = CommonController::isDirectorReviewees($employeeLogin);

            switch ($employeeLogin->position) {
                case 1:
                    //waiting for project manager
                    $progress = 2;
                    if ($isDirectorReviewees) {
                        $progress = 3;
                    }

                    break;
                case 2:
                    //waiting for director
                    $progress = 3;
                    break;
                case 3:
                    //completed
                    $progress = 4;
                    break;
                
                default:
                    if ($isDirectorReviewees && $review->employee_id == $employeeLogin->id) {
                        $progress = 3;
                    } else {
                        $progress = 1;

                        $leader = User::where('department_id', $employeeLogin->department_id)
                            ->where('position', 1)->where('user_status', 1)
                            ->first();
                        $progress = !$leader ? 2 : $progress;

                        if ($review->period < 2) {
                            $existMentor = Mentee::where('mentee_id', $employeeLogin->id)->first();
                            if ($existMentor) {
                                $progress = 0.5;
                            }

                            $isMentor = Mentee::where('mentor_id', $employeeLogin->id)->first();
                            if ($isMentor) {
                                $progress = !$leader ? 2 : 1;
                            }
                        }
                    }

                    break;
            }

            //start transaction
            DB::beginTransaction();

            $review->progress = $progress;

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

    public function undoReview(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $review = Review::findOrFail($requestDatas['review_id']);

            if (Auth()->user()->department_id != 7 && !in_array(Auth()->user()->id,[51,161])) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //start transaction
            DB::beginTransaction();

            $review->progress = $requestDatas['progress'];

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

    public function storeReviewFile(ReviewFileStoreRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            $file = $requestDatas['file'];

            //user id login
            $userId = Auth()->user()->id;

            //create an upload file folder
            $fileFolder = $this->createUpFileFolders();

            $fileName = time().'_'.$file->getClientOriginalName();
            $fileContent = file_get_contents($file);
            //file path
            $path = public_path($fileFolder.'/'.$userId.'/'.$fileName);

            //start transaction control
            DB::beginTransaction();

            $reviewFile = ReviewFile::create([
                'review_id' => $requestDatas['review_id'],
                'employee_id' => $userId,
                'employee_answer_id' => $requestDatas['employee_answer_id'],
                'file_path' => $fileFolder.'/'.$userId.'/'.$fileName
            ]);

            if ($reviewFile) {
                File::put($path, $fileContent);
            }

            DB::commit();

            $data = [
                'id' => $reviewFile->id,
                'file_path' => $reviewFile->file_path
            ];

            return response()->json($data);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteReviewFile(ReviewFileDeleteRequest $request)
    {
        try {
            $fileFolder = config('const.review_file_folder');

            //on request
            $requestDatas = $request->all();
            
            $reviewFile = ReviewFile::findOrFail($requestDatas['id']);
            $filePath = $reviewFile->file_path;

            //start transaction control
            DB::beginTransaction();

            //delete review file
            if ($reviewFile->delete()) {
                //delete avatar
                if (File::exists(public_path($filePath))) {
                    File::delete(public_path($filePath));
                }
            }

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

    /** Create an upload file folder
     * return String $path
    */
    public function createUpFileFolders()
    {
        $fileFolder = config('const.review_file_folder');
        
        $fileFolderPath = public_path($fileFolder.'/'.Auth()->user()->id);

        //create file folder
        File::ensureDirectoryExists(dirname($fileFolderPath));
        if (!is_dir($fileFolderPath)) {
            File::makeDirectory($fileFolderPath);
        }

        return $fileFolder;
    }

    private function sqlRawGetEmployees($requestDatas)
    {
        $sql = " select";
        $sql .= " u.id as id, u.avatar as avatar, u.fullname as fullname,";
        $sql .= " u.phone as phone, u.email as email, u.birthday as birthday,";
        $sql .= " u.department_id as department_id, u.position as position,";
        $sql .= " u.date_official as date_official, u.created_at as created_at,";
        $sql .= " r.id as review_id, r.start_date as review_start_date, r.next_date as review_next_date, r.progress as review_progress,";
        $sql .= " r.period as review_period";
        $sql .= " from users u";
        $sql .= " left join (";
        $sql .= " select";
        $sql .= " employee_id, id, start_date, next_date, progress, period,";
        $sql .= " row_number() over (partition by employee_id order by start_date desc, id desc) as row_num";
        $sql .= " from reviews";
        $sql .= " where reviews.deleted_at is null";
        $sql .= " ) r on u.id = r.employee_id and r.row_num = 1";
        // $sql .= " where u.user_status = 1 and u.id not in (44,46, 51, 69, 147) and u.deleted_at is null";
        $sql .= " where u.user_status = 1 and u.id not in (44,46,147) and u.deleted_at is null and u.type != 4";

        $bindings = []; // Initialize an array to hold the query bindings
        // Check if department_id exists
        if (isset($requestDatas['department_id'])) {
            $sql .= " AND u.department_id = ? ";
            $bindings[] = $requestDatas['department_id']; // Add department_id to the bindings array
        }
        // Check if employee_id exists
        if (isset($requestDatas['employee_id'])) {
            $sql .= " AND u.id = ? ";
            $bindings[] = $requestDatas['employee_id']; // Add employee_id to the bindings array
        }

        $sql .= " order by u.date_official asc, u.created_at asc";

        return DB::select($sql, $bindings);
    }

    /** Transfer Employee Data
     *
     * @param $employees
     * @return $newData
    */
    private function transferEmployeeData($employees)
    {
        $newData = array();
        $userLogin = Auth()->user();

        //list permission
        $permissions = config('const.permissions');
        //list departments
        $departments = config('const.departments');
        //list positions
        $positions = config('const.positions');
        //avatar folder path
        $avatarFolder = config('const.avatar_file_folder');
        //review statuses
        $reviewProgresses = config('const.review_progresses');
        //review period
        $reviewPeriod = config('const.review_period');

        foreach ($employees as $employee) {
            $timeReviewed = null;
            $reviewStartDateDMY = null;
            if ($employee->review_start_date) {
                $reviewStartDateDMY = Carbon::create($employee->review_start_date)->format('d/m/Y');

                $reviewStartDate = Carbon::parse($employee->review_start_date);
                $diff = Carbon::now()->diff($reviewStartDate);

                $timeReviewed = $diff->format('%a days');
            }

            $progressKey = null;
            if (!is_null($employee->review_progress)) {
                $progressKey = ($employee->review_progress == 0.5) ? 0.5 : (int) $employee->review_progress;
            }

            //Push element onto the newData array
            array_push($newData, [
                'id' => $employee->id,
                'avatar' => '/'.$avatarFolder.'/'.$employee->avatar,
                'fullname' => $employee->fullname,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'birthday' => Carbon::create($employee->birthday)->format('d/m/Y'),
                'department_id' => isset($departments[$employee->department_id]) ?
                                    $departments[$employee->department_id] : $employee->department_id,
                'position' => isset($positions[$employee->position]) ?
                                    $positions[$employee->position] : $employee->position,
                'date_official' => !empty($employee->date_official) ?
                                    Carbon::create($employee->date_official)->format('d/m/Y') : "Thử việc",
                'created_at' => Carbon::create($employee->created_at)->format('d/m/Y'),
                'review_id' => $employee->review_id,
                'review_start_date' => $reviewStartDateDMY,
                'review_next_date' => $employee->review_next_date ? Carbon::create($employee->review_next_date)->format('d/m/Y') : null,
                'review_period' => is_int($employee->review_period) ? $reviewPeriod[$employee->review_period] : null,
                'review_progress' => !is_null($progressKey) ? $reviewProgresses[(string)$progressKey] : null,
                'time_reviewed' => $timeReviewed
            ]);
        }

        return $newData;
    }

    private function transferReviewsData($reviews)
    {
        $newData = array();

        //review statuses
        $reviewProgresses = config('const.review_progresses');
        //review period
        $reviewPeriod = config('const.review_period');
        //review statuses
        $reviewStatuses = config('const.review_statuses');

        foreach ($reviews as $review) {
            $progressKey = null;
            if (!is_null($review->progress)) {
                $progressKey = ($review->progress == 0.5) ? 0.5 : (int) $review->progress;
            }
            //Push element onto the newData array
            array_push($newData, [
                'id' => $review->id,
                'fullname' => $review->fullname,
                'start_date' => Carbon::create($review->start_date)->format('Y/m/d'),
                'next_date' => Carbon::create($review->next_date)->format('Y/m/d'),
                'period' => $reviewPeriod[$review->period],
                'progress' => !is_null($progressKey) ? $reviewProgresses[(string)$progressKey] : null,
                'status' => is_int($review->status) ? $reviewStatuses[$review->status] : null
            ]);
        }

        return $newData;
    }
}
