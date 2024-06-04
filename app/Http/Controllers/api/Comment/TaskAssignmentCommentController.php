<?php

namespace App\Http\Controllers\api\Comment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\TaskAssignmentComment;
use Carbon\Carbon;

/**
 * Task Assignment Comment API
 *
 * @group Task Assignment Comment
 */
class TaskAssignmentCommentController extends Controller
{
    public function getUserLogin()
    {
        try {
            $user['id'] = Auth()->user()->id;
            $user['fullname'] = Auth()->user()->fullname;
            $user['avatar'] = Auth()->user()->avatar;

            return response()->json($user);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getListComment(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $comments = TaskAssignmentComment::join('users', 'users.id', '=', 'task_assignment_comments.user_id')
                ->select(
                    'task_assignment_comments.id as id',
                    'users.id as user_id',
                    'users.fullname as fullname',
                    'users.avatar as avatar',
                    'task_assignment_comments.comment as comment',
                    'task_assignment_comments.created_at as created_at'
                )
                ->where('task_assignment_comments.task_assignment_id', $requestDatas['task_assignment_id'])
                ->orderBy('task_assignment_comments.created_at', 'desc')
                ->get();

            return response()->json($comments);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            TaskAssignmentComment::performTransaction(function ($model) use ($requestDatas) {
                //insert task assignment comment
                TaskAssignmentComment::create([
                    'task_assignment_id' => $requestDatas['task_assignment_id'],
                    'user_id' => Auth()->user()->id,
                    'comment' => $requestDatas['comment'],
                ]);
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $comment = TaskAssignmentComment::where('id', $requestDatas['id'])
                ->where('user_id', Auth()->user()->id)
                ->first();

            if (!$comment) {
                return response()->json([]);
            }

            TaskAssignmentComment::performTransaction(function ($model) use ($comment) {
                $comment->delete();
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
