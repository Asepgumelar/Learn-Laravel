<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    private $userRepository;

    /**
     * LoginController constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {


    }

    /**
     * [OVERRIDE].
     *
     * Validate the user login request.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
    }


    /**
     * [OVERRIDE].
     *
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    protected function attemptLogin(Request $request)
    {

        return $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
       /* if ($valid) {
            $email = $request->input('email');
            $password = $request->input('password');

            $user = $this->userRepository->where('email', $email)->first();
            if (is_null($user)) {
                return false;
            }
            $newRequest = Request::create('/oauth/token', 'post', [
                'username' => $email,
                'password' => $password,
                'grant_type' => 'password',
                'client_id' => config('psa.password.client_id'),
                'client_secret' => config('psa.password.client_secret'),
            ]);
            $response = app()->handle($newRequest);

            $data = json_decode($response->getContent());
            if ($response->getStatusCode() != 200) {
                return false;
            } else {
                $request->session()->put([
                    'access_token' => $data->access_token,
                    'refresh_token' => $data->refresh_token,
                ]);
                return true;
            }
        }

        return false;*/
    }
}
