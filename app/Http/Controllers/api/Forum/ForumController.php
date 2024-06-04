<?php

namespace App\Http\Controllers\api\Forum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Database\QueryException;
use App\Repositories\ForumRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Carbon\Carbon;

/**
 * Forum API
 *
 * @group Forum
 */
class ForumController extends Controller
{
    /**
     * @var HanetRepository
     */
    private $forumRepository;

    protected $apiKey = 's1QHOT1Guwu4Vmgs8aHdXiqpPfgubajA';

    public function __construct(ForumRepository $forumRepository)
    {
        $this->forumRepository = $forumRepository;
    }

    public function redirectly()
    {
        try {
            $apiKey = $this->apiKey;

            $xfUserId = $this->forumRepository->getUserByEmail($apiKey);
            if (!$xfUserId) {
                $xfUserId = $this->forumRepository->createUser($apiKey);
            }

            $xfLoginUrl = $this->forumRepository->loginToken($apiKey, $xfUserId);

            return response()->json($xfLoginUrl);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLatestPosts()
    {
        try {
            $apiKey = $this->apiKey;

            $posts = $this->forumRepository->getLatestPosts($apiKey);

            $postsFormatted = collect($posts)->map(function ($post) {
                $formattedData = [
                    'thread_avatar_user' => isset($post['User']['avatar_urls']['o']) ? $post['User']['avatar_urls']['o'] : null,
                    'forum_title' => isset($post['Forum']['title']) ? $post['Forum']['title'] : null,
                    'last_post_date' => isset($post['last_post_date']) ? $this->convertTimestampToString($post['last_post_date']) : null,
                    'last_post_username' => isset($post['last_post_username']) ? $post['last_post_username'] : null,
                    'thread_title' => isset($post['title']) ? $post['title'] : null,
                    'thread_url' => isset($post['view_url']) ? $post['view_url'] : null,
                    'thread_id' => isset($post['thread_id']) ? $post['thread_id'] : null,
                ];
            
                return $formattedData;
            })->values()->all();

            return response()->json($postsFormatted);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }   
    }

    private function convertTimestampToString($timestamp)
    {
        // Convert the timestamp to a Carbon instance
        $carbonDate = Carbon::createFromTimestamp($timestamp);

        // Get the current date
        $currentDate = Carbon::now();

        // Determine the date label
        $dateLabel = $carbonDate->isSameDay($currentDate) ? 'Today' : ($carbonDate->isYesterday($currentDate) ? 'Yesterday' : $carbonDate->isoFormat('dddd'));

        // Format the final date string with the hour and minutes
        $formattedDate = $dateLabel . ' ' . $carbonDate->format('H:i A');

        return $formattedDate;
    }

    private function getUser()
    {
        $client = new Client();
        $res = $client->post($this->url. '/person/getCheckinByPlaceIdInTimestamp', [
            'form_params' => [
                'token' => $accessToken,
                'placeID' => $placeId,
                'type' => 0,
                'devices'=> implode(',', $devicesArr),
                'from'=> $from,
                'to'=> $to,
                'aliasID'=> $aliasID,
            ]
        ]);

        $data = json_decode($res->getBody()->getContents());
    }
}
