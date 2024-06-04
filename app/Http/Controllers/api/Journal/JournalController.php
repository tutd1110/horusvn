<?php

namespace App\Http\Controllers\api\Journal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Journal;
use App\Models\JournalFile;
use App\Models\JournalDepartment;
use App\Models\JournalGame;
use App\Models\User;
use App\Http\Requests\api\Journal\GetJournalRequest;
use App\Http\Requests\api\Journal\JournalRegisterRequest;
use App\Http\Requests\api\Journal\JournalEditRequest;
use File;
use Carbon\Carbon;

/**
 * Journal API
 *
 * @group Journal
 */
class JournalController extends Controller
{
    public function getSelbox()
    {
        try {
            //list users by department with job
            $departments = config('const.departments_with_job');
            $games = config('const.games');
            $status = config('const.view_status');
            $permission = config('const.petitions_full_permission');
            $view_status = in_array(Auth()->user()->id, $permission) ? true : false;

            $users = User::select('id', 'fullname')
                        ->where('user_status', '!=', 2)
                        ->get();

            $data = [
                'departments' => $departments,
                'games' => $games,
                'status' => $status,
                'users' => $users,
                'view_status' => $view_status,
            ];

            return response()->json($data);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getJournals(GetJournalRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $journals = [];
            if ($requestDatas['type'] === 'Department') {
                $result = $this->getJournalsDepartment($requestDatas);

                $journals = $this->mapDepartmentName($result);
            } elseif ($requestDatas['type'] === 'Game') {
                $result = $this->getJournalsGame($requestDatas);

                $journals = $this->mapGameName($result);
            } elseif ($requestDatas['type'] === 'Company') {
                $journals = $this->getJournalsCompany($requestDatas);
            }

            return response()->json($this->splitJournalFiles($journals));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getJournalsDepartment($requestDatas)
    {
        try {
            $permission = config('const.petitions_full_permission');

            $query = Journal::join('users', 'users.id', '=', 'journals.user_id')
            ->with(['files' => function ($query) {
                $query->select('id', 'path', 'journal_id');
            }])
            ->with(['departments' => function ($query) {
                $query->select('id', 'department_id', 'journal_id');
            }])
            ->select(
                'journals.id',
                'users.fullname',
                'journals.title',
                'journals.description',
                'journals.type',
                'journals.created_at',
                'users.id as user_id' 
            )
            ->where('journals.type', $requestDatas['type'])
            ->when(
                isset($requestDatas['department_id']) &&
                is_array($requestDatas['department_id']) &&
                count($requestDatas['department_id']) > 0,
                function ($query) use ($requestDatas) {
                    $ids = $requestDatas['department_id'];
                    $query->whereHas('departments', function ($query) use ($ids) {
                        $query->whereIn('department_id', $ids);
                    });
                }
            );
            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            if (in_array(Auth()->user()->id, $permission) && isset($requestDatas['status']) && $requestDatas['status'] == 1) {
                $query->where('journals.status', $requestDatas['status']);
            } else if (!in_array(Auth()->user()->id, $permission)) {
                $query->where(function($query) {
                    $query->where('status', 0)->orWhereNull('status');
                });
            }
            $journals = $query->orderBy('ordinal_number', 'asc')->get();

            return $journals;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getJournalsGame($requestDatas)
    {
        try {
            $permission = config('const.petitions_full_permission');
            
            $query = Journal::join('users', 'users.id', '=', 'journals.user_id')
            ->with(['files' => function ($query) {
                $query->select('id', 'path', 'journal_id');
            }])
            ->with(['games' => function ($query) {
                $query->select('id', 'game_id', 'journal_id');
            }])
            ->select(
                'journals.id',
                'users.fullname',
                'journals.title',
                'journals.description',
                'journals.type',
                'journals.created_at',
                'users.id as user_id' 
            )
            ->where('journals.type', $requestDatas['type'])
            ->when(
                isset($requestDatas['game_id']) &&
                is_array($requestDatas['game_id']) &&
                count($requestDatas['game_id']) > 0,
                function ($query) use ($requestDatas) {
                    $ids = $requestDatas['game_id'];
                    $query->whereHas('games', function ($query) use ($ids) {
                        $query->whereIn('game_id', $ids);
                    });
                }
            );
            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            if (in_array(Auth()->user()->id, $permission) && isset($requestDatas['status']) && $requestDatas['status'] == 1) {
                $query->where('journals.status', $requestDatas['status']);
            } else if (!in_array(Auth()->user()->id, $permission)) {
                $query->where(function($query) {
                    $query->where('status', 0)->orWhereNull('status');
                });
            }
            // $journals = $query->orderBy('ordinal_number', 'asc')->get();
            $journals = $query->orderBy('ordinal_number', 'asc')->orderBy('created_at', 'desc')->get();

            return $journals;
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getJournalsCompany($requestDatas)
    {
        try {
            //Role check
            if (Auth()->user()->id != 46 && Auth()->user()->id != 161) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $query = Journal::join('users', 'users.id', '=', 'journals.user_id')
            ->with(['files' => function ($query) {
                $query->select('id', 'path', 'journal_id');
            }])
            ->select(
                'journals.id',
                'users.fullname',
                'journals.title',
                'journals.description',
                'journals.type',
                'journals.created_at',
                'users.id as user_id' 
            )
            ->where('journals.type', $requestDatas['type']);
            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $journals = $query->orderBy('ordinal_number', 'asc')->get();

            return $journals;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getJournalById(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $journal = Journal::with(['files' => function ($query) {
                $query->select('id', 'path', 'journal_id');
            }])
            ->with(['departments' => function ($query) {
                $query->select('id', 'department_id', 'journal_id');
            }])
            ->with(['games' => function ($query) {
                $query->select('id', 'game_id', 'journal_id');
            }])
            ->select(
                'id',
                'title',
                'description',
                'status'
            )
            ->where('id', $requestDatas['id'])
            ->first();

            if (!$journal) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => __('MSG-E-003')
                    ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($journal);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(JournalRegisterRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            //start transaction control
            DB::beginTransaction();
            
            $journal = Journal::create([
                'title' =>  $requestDatas['title'],
                'description' => $requestDatas['description'],
                'user_id' => Auth()->user()->id,
                'type' => $requestDatas['type'],
                'status' => $requestDatas['status'],
                'ordinal_number' => 0,
            ]);

            if (isset($requestDatas['files']) && count($requestDatas['files']) > 0) {
                //create an upload file folder
                $fileFolder = $this->createUpFileFolders($journal->id);

                foreach ($requestDatas['files'] as $file) {
                    $fileName = $file->getClientOriginalName();
                    $fileContent = file_get_contents($file);

                    $path = public_path($fileFolder.'/'.$journal->id.'/'.$fileName);

                    File::put($path, $fileContent);

                    JournalFile::create([
                        'journal_id' => $journal->id,
                        'path' => $fileFolder.'/'.$journal->id.'/'.$fileName
                    ]);
                }
            }

            if (isset($requestDatas['department_id']) && count($requestDatas['department_id']) > 0) {
                foreach ($requestDatas['department_id'] as $value) {
                    JournalDepartment::create([
                        'journal_id' => $journal->id,
                        'department_id' => $value,
                    ]);
                }
            } elseif (isset($requestDatas['game_id']) && count($requestDatas['game_id']) > 0) {
                foreach ($requestDatas['game_id'] as $value) {
                    JournalGame::create([
                        'journal_id' => $journal->id,
                        'game_id' => $value,
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

    public function update(JournalEditRequest $request)
    {
        try {
            $fileFolder = config('const.journal_file_folder');

            //on request
            $requestDatas = $request->all();

            $journal = Journal::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            $journal->title = $requestDatas['title'];
            $journal->description = $requestDatas['description'];
            $journal->status = $requestDatas['status'];
            $journal->save();

            if (isset($requestDatas['files']) && count($requestDatas['files']) > 0) {
                //create an upload file folder
                $fileFolder = $this->createUpFileFolders($journal->id);

                foreach ($requestDatas['files'] as $file) {
                    $fileName = $file->getClientOriginalName();
                    $fileContent = file_get_contents($file);

                    $path = public_path($fileFolder.'/'.$journal->id.'/'.$fileName);

                    File::put($path, $fileContent);

                    JournalFile::create([
                        'journal_id' => $journal->id,
                        'path' => $fileFolder.'/'.$journal->id.'/'.$fileName
                    ]);
                }
            }

            if (isset($requestDatas['files_ids_removed']) &&
                count($requestDatas['files_ids_removed']) > 0) {
                foreach ($requestDatas['files_ids_removed'] as $value) {
                    $file = JournalFile::findOrFail($value);

                    //delete old file
                    if (File::exists(public_path($file->path))) {
                        File::delete(public_path($file->path));
                    }

                    //delete post_files record
                    $file->delete();
                }
            }

            if (isset($requestDatas['department_id']) && count($requestDatas['department_id']) > 0) {
                JournalDepartment::where('journal_id', $journal->id)->delete();

                foreach ($requestDatas['department_id'] as $value) {
                    JournalDepartment::create([
                        'journal_id' => $journal->id,
                        'department_id' => $value,
                    ]);
                }
            } elseif (isset($requestDatas['game_id']) && count($requestDatas['game_id']) > 0) {
                JournalGame::where('journal_id', $journal->id)->delete();

                foreach ($requestDatas['game_id'] as $value) {
                    JournalGame::create([
                        'journal_id' => $journal->id,
                        'game_id' => $value,
                    ]);
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
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateOrder(Request $request)
    {
        try {
            //on reuqest
            $requestDatas = $request->all();

            $journals = $requestDatas['journals'];
            //start transaction
            DB::beginTransaction();
            // Loop through the journals and update the order in the database
            foreach ($journals as $journal) {
                $id = $journal['id'];
                $ordinalNumber = $journal['ordinal_number'];

                $journal = Journal::findOrFail($id);
                $journal->ordinal_number = $ordinalNumber;
                $journal->save();
            }
            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $fileFolder = config('const.journal_file_folder');

            //on reuqest
            $requestDatas = $request->all();

            $journal = Journal::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete post
            $journal->delete();

            JournalDepartment::where('journal_id', $requestDatas['id'])->delete();
            JournalGame::where('journal_id', $requestDatas['id'])->delete();

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

    /** Append SQL if json is requested
     *
     * @param  $requestDatas
     * @param  $query
     * @return $addedQuery
    */
    private function addSqlWithSorting($requestDatas, $query)
    {
        $addedQuery = $query;

        //Change the SQL according to the requested search conditions
        if (isset($requestDatas['user_id']) && count($requestDatas['user_id']) > 0) {
            $addedQuery = $addedQuery->whereIn('journals.user_id', $requestDatas['user_id']);
        }

        if (!empty($requestDatas['start_time'])) {
            $addedQuery = $addedQuery->whereDate(
                'journals.created_at',
                '>=',
                Carbon::create($requestDatas['start_time'])->format('Y/m/d 00:00:00')
            );
        }

        if (!empty($requestDatas['end_time'])) {
            $addedQuery = $addedQuery->whereDate(
                'journals.created_at',
                '<=',
                Carbon::create($requestDatas['end_time'])->format('Y/m/d 23:59:59')
            );
        }

        return $addedQuery;
    }

    private function mapDepartmentName($result)
    {
        try {
            //list users by department with job
            $departmentNames = config('const.departments_with_job');

            $departmentNamesMap = collect($departmentNames)->keyBy('value');
            $collection = collect($result);

            $collection->transform(function ($journal) use ($departmentNamesMap) {
                $journal['departments'] = collect($journal['departments'])->map(
                    function ($department) use ($departmentNamesMap) {
                        $departmentId = $department['department_id'];

                        $department['name'] = $departmentNamesMap->get($departmentId)['label'] ?? 'Unknown';
                        unset($department['id'], $department['journal_id']);

                        return $department;
                    }
                )->values()->all();

                return $journal;
            });

            $modifiedData = $collection->all();

            return $modifiedData;
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    private function mapGameName($result)
    {
        try {
            //list users by department with job
            $gameNames = config('const.games');

            $gameNamesMap = collect($gameNames)->keyBy('value');
            $collection = collect($result);

            $collection->transform(function ($journal) use ($gameNamesMap) {
                $journal['games'] = collect($journal['games'])->map(
                    function ($game) use ($gameNamesMap) {
                        $gameId = $game['game_id'];

                        $game['name'] = $gameNamesMap->get($gameId)['label'] ?? 'Unknown';
                        unset($game['id'], $game['journal_id']);

                        return $game;
                    }
                )->values()->all();

                return $journal;
            });

            $modifiedData = $collection->all();

            return $modifiedData;
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    private function splitJournalFiles($journals)
    {
        try {
            $collection = collect($journals);

            $collection->transform(function ($journal) {
                $imageFiles = [];
                $otherFiles = [];

                collect($journal['files'])->each(function ($file) use (&$imageFiles, &$otherFiles) {
                    $extension = pathinfo($file['path'], PATHINFO_EXTENSION);

                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                        $imageFiles[] = $file;
                    } else {
                        $otherFiles[] = $file;
                    }
                });

                $journal['image_files'] = $imageFiles;
                $journal['other_files'] = $otherFiles;
                unset($journal['files']);

                return $journal;
            });

            return $collection;
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    /** Create an upload file folder
     * return String $path
    */
    public function createUpFileFolders($journalId)
    {
        $fileFolder = config('const.journal_file_folder');
        
        $fileFolderPath = public_path($fileFolder.'/'.$journalId);

        //create file folder
        File::ensureDirectoryExists(dirname($fileFolderPath));
        if (!is_dir($fileFolderPath)) {
            File::makeDirectory($fileFolderPath);
        }

        return $fileFolder;
    }
}
