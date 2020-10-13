<?php

namespace App\Libraries;

use App\Exceptions\InvalidCredentialsException;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use Unirest\Request as UniRequest;
use Unirest\Request\Body as UniBody;

class LoginProxy
{
    use HandlesOAuthErrors;

    const REFRESH_TOKEN = 'refreshToken';


    private $auth;

    private $cookie;

    private $db;

    private $request;

    private $userRepository;

    private $app;

    public function __construct(Application $app, UserRepository $userRepository)
    {
        $this->app = $app;
        $this->userRepository = $userRepository;
        $this->auth = $app->make('auth');
        $this->cookie = $app->make('cookie');
        $this->db = $app->make('db');
        $this->request = $app->make('request');
    }

    /**
     * @param $email
     * @param $password
     * @return array
     * @throws InvalidCredentialsException
     */
    public function attemptLogin($email, $password)
    {
        $user = $this->userRepository->where('email', $email)->first();

        if (!is_null($user)) {
            return $this->proxy('password', [
                'username' => $email,
                'password' => $password
            ]);
        }
    }

    /**
     * Attempt to refresh the access token used a refresh token that
     * has been saved in a cookie
     */
    public function attemptRefresh($token)
    {
        return $this->proxy('refresh_token', [
            'refresh_token' => $token
        ]);
    }

    public function proxy($grantType, array $data = [])
    {
        $data = array_merge($data, [
            'client_id' => config('psa.password.client_id'),
            'client_secret' => config('psa.password.client_secret'),
            'grant_type' => $grantType,
        ]);
        $curlOptions = array(
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSLVERSION => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60
        );
        UniRequest::curlOpts($curlOptions);
        UniRequest::jsonOpts(true, 512, JSON_UNESCAPED_SLASHES);
        UniRequest::verifyPeer(false);
        $body = UniBody::Form($data);
        $headers = array('Accept' => 'application/json');
        $uri = config('app.url') . '/' . config('psa.oauth_client_url');
        $response = UniRequest::post($uri, $headers, $body);
        if ($response->code != 200) {
            return [
                'header' => 401,
                'errors' => $response->body
            ];
        }
        return [
            'header' => 200,
            'token_type' => $response->body['token_type'],
            'access_token' => $response->body['access_token'],
            'refresh_token' => $response->body['refresh_token'],
            'expires_in' => $response->body['expires_in']
        ];
    }

    /**
     * Logs out the user. We revoke access token and refresh token.
     * Also instruct the client to forget the refresh cookie.
     */
    public function logout()
    {
        $accessToken = \Illuminate\Support\Facades\Auth::user()->token();
        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);
        $accessToken->revoke();
    }
}
