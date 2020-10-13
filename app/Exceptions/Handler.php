<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \League\OAuth2\Server\Exception\OAuthServerException::class,
    ];

    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    public function render($request, Exception $exception)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $response = [
                'meta' => [
                    'code' => 400,
                    'api_version' => '1.0',
                    'message' => 'Error',
                    'method' => $request->server('REQUEST_METHOD'),
                ],
                'errors' => [$exception->getMessage()],
                'data' => [
                    'message' => (string)$exception->getMessage(),
                    'items' => []
                ]
            ];
            if (env('APP_DEBUG') == true) {
                /*$response['meta']['debug'] = [
                    'exception' => get_class($exception),
                    'trace' => $exception->getTrace()
                ];*/
            } else {
                $response['meta']['debug'] = [
                    'exception' => null,
                    'trace' => null
                ];
            }
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $response['meta']['code'] = $exception->getStatusCode();
                return response()->json($response, $exception->getStatusCode());
            } else if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $response['meta']['code'] = Response::HTTP_NOT_FOUND;
                return response()->json($response, Response::HTTP_NOT_FOUND);
            } else if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $response['meta']['code'] = Response::HTTP_UNAUTHORIZED;
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            } else if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
                $response['meta']['code'] = Response::HTTP_UNAUTHORIZED;
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            } else if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
                $response['meta']['code'] = Response::HTTP_UNAUTHORIZED;
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            } else if ($exception instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                $response['meta']['code'] = Response::HTTP_UNAUTHORIZED;
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            } else if ($exception instanceof \Laravel\Passport\Exceptions\OAuthServerException) {
                $response['meta']['code'] = Response::HTTP_UNAUTHORIZED;
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            } else if ($exception instanceof \League\OAuth2\Server\Exception\OAuthServerException) {
                $response['meta']['code'] = Response::HTTP_UNAUTHORIZED;
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            } else if ($exception instanceof \League\OAuth2\Server\Exception\OAuthServerException) {
                $response['meta']['code'] = Response::HTTP_UNAUTHORIZED;
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            }
            if (!($exception instanceof \Illuminate\Validation\ValidationException)) {
                $response['meta']['code'] = 400;
                return response()->json($response, 400);
            }
            return parent::render($request, $exception);
        }
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Auth\AuthenticationException $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($exception->guards()[0] === 'web') {
            return redirect()->guest($exception->redirectTo() ?? route('login'));
        } else {
            $response = [
                'meta' => [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'api_version' => '1.0',
                    'message' => 'Error',
                    'method' => $request->server('REQUEST_METHOD'),
                ],
                'errors' => [$exception->getMessage()],
                'data' => [
                    'message' => (string)$exception->getMessage(),
                    'items' => []
                ]
            ];
            return response()->json($response, Response::HTTP_UNAUTHORIZED);
        }
    }
}

