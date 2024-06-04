<?php

namespace App\Http\Controllers\api\Task;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\TaskFile;
use App\Http\Requests\api\Task\TaskFile\TaskFileStoreRequest;
use File;
use Carbon\Carbon;

/**
 * Task File API
 *
 * @group Task File
 */
class TaskFileController extends Controller
{
    /** Task File Store
     *
     * @group Task
     *
     * @bodyParam task_id bigint required ID công việc
     * @bodyParam file file nullable Tệp tin đính kèm
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "ID công việc không tồn tại",
     *      "errors_list": {
     *          "title": [
     *              "ID công việc không tồn tại"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(TaskFileStoreRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            TaskFile::performTransaction(function ($model) use ($requestDatas) {
                $file = $requestDatas['file'];
                $taskId = $requestDatas['task_id'];

                //create an upload file folder
                $fileFolder = $this->createUpFileFolders($taskId);

                $fileName = time().'_'.$file->getClientOriginalName();
                $fileContent = file_get_contents($file);
                //file path
                $path = public_path($fileFolder.'/'.$taskId.'/'.$fileName);

                $taskFile = TaskFile::create([
                    'task_id' => $taskId,
                    'path' => $fileFolder.'/'.$taskId.'/'.$fileName
                ]);

                if ($taskFile) {
                    File::put($path, $fileContent);
                }
            });

            $data = [
                'id' => $taskFile->id,
                'path' => $taskFile->path
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $fileFolder = config('const.task_file_folder');

            //on request
            $requestDatas = $request->all();
            
            $taskFile = TaskFile::findOrFail($requestDatas['id']);
            $filePath = $taskFile->path;

            TaskFile::performTransaction(function ($model) use ($taskFile, $filePath) {
                //delete review file
                if ($taskFile->delete()) {
                    //delete avatar
                    if (File::exists(public_path($filePath))) {
                        File::delete(public_path($filePath));
                    }
                }
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

    /** Create an upload file folder
     * return String $path
    */
    public function createUpFileFolders($taskId)
    {
        $fileFolder = config('const.task_file_folder');
        
        $fileFolderPath = public_path($fileFolder.'/'.$taskId);

        //create file folder
        File::ensureDirectoryExists(dirname($fileFolderPath));
        if (!is_dir($fileFolderPath)) {
            File::makeDirectory($fileFolderPath);
        }

        return $fileFolder;
    }
}
