<?php


namespace App\Services;


use Exception;
use Illuminate\Support\Facades\Log;
use Unirest\Request;

class DisbursementService
{
    private $apiKey;
    private $apiSecret;
    private $httpClient;

    public function __construct()
    {
        $this->apiKey = env('XENDIT_API_KEY');
        $this->apiSecret = env('XENDIT_API_SECRET');
        $this->httpClient = new Request();
    }

    public function createDisbursement($params)
    {
        $response = null;
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];
            $this->httpClient->auth(env('XENDIT_API_SECRET'), '');
            $this->httpClient->jsonOpts(true, 512, JSON_UNESCAPED_SLASHES);
            $response = $this->httpClient->post(env("XENDIT_BASE_URL") . '/disbursements', $headers, json_encode($params));
            if ($response->code != 200) {
                throw new Exception("error create xendit disbursement, info " . $response->raw_body);
            }
            return $response;
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }
        return $response;
    }

    public function getDisbursementById($id)
    {

    }

    public function getDisbursementByExternalId($externalId)
    {

    }

    public function getDisbursementAvailableBanks()
    {

    }
}
