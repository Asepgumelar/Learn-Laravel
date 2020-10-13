<?php
/**
 * Created by PhpStorm.
 * User: thomzee
 * Date: 11/12/2018
 * Time: 16:59
 */

namespace App\Libraries\Api;


use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseLibrary
{
    public function listPaginate($collection, $limit)
    {
        $return = [];
        $paginated = $collection->paginate($limit);
        $return['meta']['code'] = 200;
        $return['meta']['message'] = trans('message.api.success');
        $return['meta']['total'] = $paginated->total();
        $return['meta']['per_page'] = $paginated->perPage();
        $return['meta']['current_page'] = $paginated->currentPage();
        $return['meta']['last_page'] = $paginated->lastPage();
        $return['meta']['has_more_pages'] = $paginated->hasMorePages();
        $return['meta']['from'] = $paginated->firstItem();
        $return['meta']['to'] = $paginated->lastItem();
        $return['links']['self'] = url()->full();
        $return['links']['next'] = $paginated->nextPageUrl();
        $return['links']['prev'] = $paginated->previousPageUrl();
        $return['data'] = $paginated->items();
        return $return;
    }

    public function successResponse()
    {
        $return = [];
        $return['meta']['code'] = 200;
        $return['meta']['message'] = trans('message.api.success');
        return $return;
    }

    public function dataResponse($data)
    {
        $return = [];
        $return['meta']['code'] = 200;
        $return['meta']['message'] = trans('message.api.success');
        $return['data'] = $data;
        return $return;
    }

    public function createResponse($code, $data, $message = null)
    {
        $return = [];
        $return['meta']['code'] = $code;
        $return['meta']['message'] = $message === null ? trans('message.api.success') : $message;
        $return['data'] = $data;
        return $return;
    }

    public function errorResponse(\Exception $e)
    {
        $return = [];
        $return['meta']['code'] = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $return['meta']['message'] = trans('message.api.error');
        $return['meta']['error'] = $e->getMessage();
        return $return;
    }

    public function customFailResponse($code, $errors)
    {
        $return = [];
        $return['meta']['code'] = $code;
        $return['meta']['message'] = trans('message.api.error');
        $return['meta']['errors'] = $errors;
        $return['data'] = [];
        return $return;
    }


    public function validationFailResponse($errors)
    {
        $return = [];
        $return['meta']['code'] = 422;
        $return['meta']['message'] = trans('message.api.error');
        $return['meta']['errors'] = $errors;
        $return['data'] = [];
        return $return;
    }

    private $errorMessages = null;

    public function setErrorMessage($data){
        $this->errorMessages = $data['message'];
        $return = [];
        $return['meta']['code'] = 401;
        $return['meta']['message'] = $this->errorMessages;
        $return['data'] = [];
        return $return;
    }

    public function getErrorMessage(){
        return $this->errorMessages;
    }
}
