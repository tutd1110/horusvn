<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use \Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LoginController API
 *
 * @group LoginController
 */
final class LoginController extends Controller
{
    const HOME_URL = 'home';

    /**
     * @param AuthManager $auth
     */
    public function __construct(
        private AuthManager $auth,
    ) {
    }

    /** Login
     *
     * @group LoginController
     *
     * @bodyParam email string required Email
     * @bodyParam password string required Password
     *
     * @response 200 {
     *   'redirect_path' : /home,
     * }
     * @response 403 {
     *    'status' : 403,
     *    'errors' : 'XXXXXXXX'
     * }
     * @response 422 {
     *    'status' : 422,
     *    'errors' : 'XXXXXXXX'
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            /******Prepare*******/
            $credentials = $request->only(['email', 'password',]);
            $systemDatetime =  new Carbon();
            $requestDatas = $request->all();

            if (!isset($requestDatas['email']) || !isset($requestDatas['password'])) {
                return response()->json(['status' => Response::HTTP_UNAUTHORIZED,
                                            'errors' => __('MSG-E-002')], Response::HTTP_UNAUTHORIZED);
            }

            $user = User::where('email', $requestDatas['email'])->first();
            if (!$user) {
                return response()->json(['status' => Response::HTTP_UNAUTHORIZED,
                                        'errors' => __('MSG-E-002')], Response::HTTP_UNAUTHORIZED);
            }

            /******Certification*******/
            //start transaction control
            DB::beginTransaction();

            //for successful authentication
            if ($this->isAuthenticationSuccessful($credentials, $user)) {
                $request->session()->regenerate();

                //update on successful authentication
                $this->initializeAccount($user);
                
                //get redirect URL
                DB::commit();
                return new JsonResponse(['redirect_path' => $this->getRedirectPath(self::HOME_URL)]);
            }

            //in case of authentication failure
            return response()->json(['status' => Response::HTTP_UNAUTHORIZED,
                                        'errors' => __('MSG-E-002')], Response::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                                        'errors' => $e->getMessage(),], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Whether the authentication succeeds
     *
     * @param  credentials
     * @param  User $user
    */
    private function isAuthenticationSuccessful($credentials, User $user): bool
    {
        //authentication failed
        //check with email and password, if unsuccessful
        if (!($this->auth->guard()->attempt($credentials))) {
            return false;
        }

        //authentication success
        //user is activity
        if ($user['user_status'] == 1) {
            return true;
        }
      
        //authentication failure other than the above
        return false;
    }

    /** Initialize account
     *
     * @param $user
    */
    private function initializeAccount(User $user)
    {
        try {
            //do something here if you wanna update user
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                                        'errors' => $e->getMessage(),], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Specify redirect destination after authentication
     *
     * @return String $redirectPath
    */
    private function getRedirectPath($redirectPath):String
    {
        //When there is a screen that was attempted to access immediately before authentication
        //(there is a transition source)
        if (session()->has('url.intended')) {
            //redirected to attempted screen
            $url = parse_url(redirect()->intended()->getTargetUrl(), PHP_URL_PATH);

            return $url ? '/'.basename($url) : '/'.$redirectPath;
        }

        return '/'.$redirectPath;
    }
}
