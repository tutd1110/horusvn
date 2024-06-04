<?php

namespace App\Http\Controllers\api\Post;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\User;
use App\Http\Requests\api\Post\PostRegisterRequest;
use App\Http\Requests\api\Post\PostEditRequest;
use File;
use Carbon\Carbon;

/**
 * Post API
 *
 * @group Post
 */
class PostController extends Controller
{
    public function latest()
    {
        try {
            $posts = Post::join('users', 'users.id', '=', 'posts.author_id')
            ->with(['postFiles' => function ($query) {
                $query->select('id', 'path', 'post_id');
            }])
            ->select(
                'posts.id',
                'users.fullname',
                'users.avatar',
                'posts.title',
                'posts.content',
                'posts.status',
                'posts.created_at'
            )
            ->where('status', 1)
            ->orderByDesc('posts.created_at')
            ->get();

            return response()->json($posts);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getListPosts(Request $request)
    {
        try {
            $posts = Post::join('users', 'users.id', '=', 'posts.author_id')
                ->select(
                    'posts.id',
                    'posts.title',
                    'posts.status',
                    'users.fullname as fullname',
                    'posts.created_at'
                )
                ->get();

            return response()->json($posts);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPostById(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $post = Post::with(['postFiles' => function ($query) {
                $query->select('id', 'path', 'post_id');
            }])
            ->select(
                'id',
                'title',
                'content',
                'status',
                'created_at'
            )
            ->where('id', $requestDatas['id'])
            ->first();

            if (!$post) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => __('MSG-E-003')
                    ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($post);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Post Store
     *
     * @group Post
     *
     * @bodyParam title string required Tiêu đề
     * @bodyParam content text required Nội dung
     * @bodyParam files array file nullable Tệp tin đính kèm
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Tiêu đề không được để trống",
     *      "errors_list": {
     *          "title": [
     *              "Tiêu đề không được để trống"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(PostRegisterRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            //start transaction control
            DB::beginTransaction();

            $post = Post::create([
                'title' =>  $requestDatas['title'],
                'content' => $requestDatas['content'],
                'author_id' => Auth()->user()->id,
                'status' => $requestDatas['status']
            ]);

            if (isset($requestDatas['files']) && count($requestDatas['files']) > 0) {
                //create an upload file folder
                $fileFolder = $this->createUpFileFolders($post->id);

                foreach ($requestDatas['files'] as $file) {
                    $fileName = time().'_'.$file->getClientOriginalName();
                    $fileContent = file_get_contents($file);

                    $path = public_path($fileFolder.'/'.$post->id.'/'.$fileName);

                    File::put($path, $fileContent);

                    PostFile::create([
                        'post_id' => $post->id,
                        'path' => $fileFolder.'/'.$post->id.'/'.$fileName
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-004'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(PostEditRequest $request)
    {
        try {
            $fileFolder = config('const.post_file_folder');

            //on request
            $requestDatas = $request->all();

            $post = Post::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            $post->title = $requestDatas['title'];
            $post->content = $requestDatas['content'];
            $post->status = $requestDatas['status'];

            $post->save();

            if (isset($requestDatas['files']) && count($requestDatas['files']) > 0) {
                //create an upload file folder
                $fileFolder = $this->createUpFileFolders($post->id);

                foreach ($requestDatas['files'] as $file) {
                    $fileName = time().'_'.$file->getClientOriginalName();
                    $fileContent = file_get_contents($file);

                    $path = public_path($fileFolder.'/'.$post->id.'/'.$fileName);

                    File::put($path, $fileContent);

                    PostFile::create([
                        'post_id' => $post->id,
                        'path' => $fileFolder.'/'.$post->id.'/'.$fileName
                    ]);
                }
            }

            if (isset($requestDatas['post_files_ids_removed']) &&
                count($requestDatas['post_files_ids_removed']) > 0) {
                foreach ($requestDatas['post_files_ids_removed'] as $value) {
                    $postFile = PostFile::findOrFail($value);

                    //delete old file
                    if (File::exists(public_path($postFile->path))) {
                        File::delete(public_path($postFile->path));
                    }

                    //delete post_files record
                    $postFile->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $fileFolder = config('const.post_file_folder');

            //on reuqest
            $requestDatas = $request->all();

            $post = Post::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete post
            $post->delete();

            $folderPath = public_path($fileFolder.'/'.$requestDatas['id']);
            if (is_dir($folderPath)) {
                File::deleteDirectory($folderPath);
            }

            DB::commit();

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

    /** Create an upload file folder
     * return String $path
    */
    public function createUpFileFolders($postId)
    {
        $fileFolder = config('const.post_file_folder');
        
        $fileFolderPath = public_path($fileFolder.'/'.$postId);

        //create file folder
        File::ensureDirectoryExists(dirname($fileFolderPath));
        if (!is_dir($fileFolderPath)) {
            File::makeDirectory($fileFolderPath);
        }

        return $fileFolder;
    }
}
