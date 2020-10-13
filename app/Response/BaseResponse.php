<?php


namespace App\Response;


use App\Mappers\BaseMapper;
use Illuminate\Http\JsonResponse;

class BaseResponse
{
    public function list($paged, BaseMapper $mapper, array $options = [])
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_OK;
        $return['meta']['message'] = trans('message.api.success');
        $return['meta']['total'] = $paged->total();
        $return['meta']['per_page'] = $paged->perPage();
        $return['meta']['current_page'] = $paged->currentPage();
        $return['meta']['last_page'] = $paged->lastPage();
        $return['meta']['has_more_pages'] = $paged->hasMorePages();
        $return['meta']['from'] = $paged->firstItem();
        $return['meta']['to'] = $paged->lastItem();
        $return['links']['self'] = url()->full();
        $return['links']['next'] = $paged->nextPageUrl();
        $return['links']['prev'] = $paged->previousPageUrl();
        $return['data'] = $mapper->list($paged->items());

        foreach($options as $key => $option) {
            $return[$key] = $option;
        }

        return $return;
    }

    public function single($data, BaseMapper $mapper, array $options = [])
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_OK;
        $return['meta']['message'] = trans('message.api.success');
        $return['data'] = $mapper->single($data);

        foreach($options as $key => $option) {
            $return[$key] = $option;
        }

        return $return;
    }

    public function singleInArray($data, BaseMapper $mapper, array $options = [])
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_OK;
        $return['meta']['message'] = trans('message.api.success');
        $return['data'][] = $mapper->single($data);

        foreach($options as $key => $option) {
            $return[$key] = $option;
        }

        return $return;
    }

    public function listWithoutPaging($data, BaseMapper $mapper, array $options = [])
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_OK;
        $return['meta']['message'] = trans('message.api.success');
        $return['data'] = $mapper->list($data);

        foreach($options as $key => $option) {
            $return[$key] = $option;
        }

        return $return;
    }

    public function success()
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_OK;
        $return['meta']['message'] = trans('message.api.success');
        $return['data'] = (object) [];
        return $return;
    }

    public function error(\Exception $e)
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $return['meta']['message'] = trans('message.api.error');
        $return['meta']['error'] = $e->getMessage();
        $return['data'] = (object) [];
        return $return;
    }

    public function customError($code, $errors)
    {
        $return = [];
        $return['meta']['code'] = $code;
        $return['meta']['message'] = trans('message.api.error');
        $return['meta']['errors'] = $errors;
        $return['data'] = (object) [];
        return $return;
    }

    public function validationFail($errors)
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
        $return['meta']['message'] = trans('message.api.error');
        $return['meta']['errors'] = $errors;
        $return['data'] = (object) [];
        return $return;
    }

    /**
     * Full custom response data.
     *
     * @param int $code
     * @param string $message
     * @param string $error
     * @param array|object $data
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function fullCustom($code, $message, $error, $data = [], $errors = [])
    {
        $response = [
            'meta' => [
                'code' => $code,
                'error' => $error,
                'message' => $message,
                'errors' => $errors
            ],
            'data' => $data
        ];
        return response()->json($response, $code);
    }
}