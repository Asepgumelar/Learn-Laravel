<?php

namespace App\Libraries;

use App\Libraries\Api\ResponseLibrary;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

/**
 * WaveCell Rest Libraries.
 *
 * @author     Odenktools
 * @license    MIT
 * @package    \App\Libraries
 * @copyright  (c) 2019, PerluApps Technology
 */
class WaveCellLibrary
{
    protected $configuration = [];

    private $responseLib;

    /**
     * Default constructor untuk konfigurasi.
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        $this->responseLib = new ResponseLibrary();
        $this->configuration['url'] = isset($data['url']) ? $data['url'] : env('WAVE_CELL_URL');
        $this->configuration['source'] = isset($data['source']) ? $data['source'] : env('WAVE_CELL_FROM');
        $this->configuration['secret'] = isset($data['secret']) ? $data['secret'] : env('WAVE_CELL_SECRET');
        $this->configuration['codelength'] = isset($data['codelength']) ? $data['codelength'] : env('WAVE_CODE_LENGTH');
        $this->configuration['validate'] = isset($data['validate']) ? $data['validate'] : env('WAVE_CELL_VALIDITY');
        $this->configuration['resendingInterval'] = isset($data['resendingInterval']) ? $data['resendingInterval'] : env('WAVE_CELL_RESEND');
        $this->configuration['sub_account_id'] = isset($data['sub_account_id']) ? $data['sub_account_id'] : env('WAVE_CELL_SUBACCOUNT_ID');
        $this->configuration['otp_sub_account_id'] = isset($data['otp_sub_account_id']) ? $data['otp_sub_account_id'] : env('WAVE_CELL_OTP_SUBACCOUNT_ID');
    }

    /**
     * Sending SMS kepada customer untuk keperluan PROMO atau yang lainnya. Ini secara Single,
     * <br/>
     * tidak boleh mempergunakannya secara bulk.
     * @param $destination . Nomor yang akan dikirimkan SMS
     * @param $content . Nilai SMS yang akan dikirimkan.
     * @return \Psr\Http\Message\ResponseInterface|array|mixed
     */
    public function sendSingleSms($destination, $content)
    {
        try {
            $url = $this->configuration['url'] . '/sms/v1/' . $this->configuration['sub_account_id'] . '/single';
            $client = new Client();

            $body = array();
            $body['source'] = $this->configuration['source'];
            $body['destination'] = $destination;
            $body['text'] = Str::limit($content, 160, '');
            $body['encoding'] = 'AUTO';

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->configuration['secret'],
                    'Content-Type' => 'application/json'
                ],
                'json' => $body
            ]);
            if ($response->getBody()) {
                $responseBody = \GuzzleHttp\json_decode($response->getBody(), true);
                return $responseBody;
            }
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * Sending SMS kepada customer untuk keperluan PROMO atau yang lainnya. Ini secara Single,
     * <br/>
     * tidak boleh mempergunakannya secara bulk.
     * @param $destination . Nomor yang akan dikirimkan SMS
     * @param $content . Nilai SMS yang akan dikirimkan.
     * @return \Psr\Http\Message\ResponseInterface|array|mixed
     */
    public function sendSingleSmsForgot($destination, $content)
    {
        try {
            $url = $this->configuration['url'] . '/sms/v1/' . $this->configuration['sub_account_id'] . '/single';
            $client = new Client();

            $body = array();
            $body['source'] = $this->configuration['source'];
            $body['destination'] = $destination;
            $body['text'] = Str::limit($content, 160, '');
            $body['encoding'] = 'AUTO';

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->configuration['secret'],
                    'Content-Type' => 'application/json'
                ],
                'json' => $body
            ]);

            if ($response->getBody()) {
                $responseBody = array('code' => $response->getStatusCode(), "data" => array());
                if ($response->getStatusCode() !== 200) {
                    $errors = [];
                    $errors['code'] = 400;
                    $errors['errors'] = \GuzzleHttp\json_decode($response->getBody(), true);
                    $errors['message'] = trans('message.api.error');
                    $errors['meta']['code'] = 400;
                    $errors['meta']['message'] = trans('message.api.error');
                    $errors['meta']['errors'] = \GuzzleHttp\json_decode($response->getBody(), true);
                    $errors['data'] = [];
                    return $errors;
                }
                array_push($responseBody["data"],
                    \GuzzleHttp\json_decode($response->getBody(), true));
                return $responseBody;
            }
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $errors = [];
            $errors['code'] = $exception->getCode();
            $errors['message'] = trans('message.api.error');
            $errors['errors'] = $exception->getMessage();
            $errors['meta']['code'] = $exception->getCode();
            $errors['meta']['message'] = trans('message.api.error');
            $errors['meta']['errors'] = array($exception->getMessage());
            $errors['data'] = [];
            return $errors;
        }
    }

    /**
     * Sending OTP ke WaveCell.
     *
     * @param $destination . Nomor yang akan dikirimkan melalui OTP
     * @return array|mixed
     */
    public function sendOtp($destination)
    {
        try {
            $url = $this->configuration['url'] . '/otp/v1/' . $this->configuration['otp_sub_account_id'];
            $client = new Client();

            $body = array();
            $body['country'] = 'ID';
            $body['destination'] = $destination;
            $body['productName'] = $this->configuration['source'];
            $body['codeLength'] = $this->configuration['codelength'];
            $body['codeValidity'] = $this->configuration['validate']; //Default 5 Menit
            $body['resendingInterval'] = $this->configuration['resendingInterval'];
            $body['createNew'] = true;

            $body['template'] = array(
                "source"=> "WL INFO",
                "text"=> "Kode Verifikasi {productName} : {code}. JANGAN BERIKAN KODE RAHASIA INI KEPADA SIAPA PUN.",
                "encoding"=> "AUTO",
            );

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->configuration['secret'],
                    'Content-Type' => 'application/json'
                ],
                'json' => $body
            ]);

            $responseBody = array('code' => $response->getStatusCode(), "data" => array());
            if ($response->getStatusCode() == 200) {
                array_push($responseBody["data"],
                    \GuzzleHttp\json_decode($response->getBody(), true));
                return $responseBody;
            } else {
                $errors = [];
                $errors['code'] = 400;
                $errors['errors'] = array('unknown error');
                $errors['message'] = trans('message.api.error');
                $errors['meta']['code'] = 400;
                $errors['meta']['message'] = trans('message.api.error');
                $errors['meta']['errors'] = \GuzzleHttp\json_decode($response->getBody(), true);
                $errors['data'] = [];
                return $errors;
            }
        } catch (\GuzzleHttp\Exception\ClientException $exception) {

            $errors = [];
            $errors['code'] = $exception->getCode();
            $errors['message'] = trans('message.api.error');
            $errors['errors'] = array('unknown error');
            $errors['meta']['code'] = $exception->getCode();
            $errors['meta']['message'] = trans('message.api.error');
            $errors['meta']['errors'] = array($exception->getMessage());
            $errors['data'] = [];
            return $errors;
        }
    }

    /**
     * Verifikasi OTP untuk keperluan mobile.
     * @param $uid
     * @param $code
     * @return array|mixed
     */
    public function verifyOtp($uid, $code)
    {
        try {
            $url = $this->configuration['url'] . "/verify/v1/" .
                $this->configuration['sub_account_id'] . "/$uid?code=$code";
            $client = new Client();
            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->configuration['secret'],
                    'Content-Type' => 'application/json'
                ]
            ]);
            $responseBody = array('code' => $response->getStatusCode(), "data" => array());
            if ($response->getStatusCode() == 200) {
                $data = \GuzzleHttp\json_decode($response->getBody(), true);
                //Jika status bukan VERIFIED
                if ($data['status'] !== 'VERIFIED') {
                    $errors = [];
                    $errors['code'] = 400;
                    $errors['errors'] = $data;
                    $errors['message'] = trans('message.api.error');
                    $errors['meta']['code'] = 400;
                    $errors['meta']['message'] = trans('message.api.error');
                    $errors['meta']['errors'] = array('error');
                    $errors['data'] = [];
                    return $errors;
                } else {
                    array_push($responseBody["data"], $data);
                    return $responseBody;
                }
            } else {
                $errors = [];
                $errors['code'] = 400;
                $errors['errors'] = array('unknown error');
                $errors['message'] = trans('message.api.error');
                $errors['meta']['code'] = 400;
                $errors['meta']['message'] = trans('message.api.error');
                $errors['meta']['errors'] = \GuzzleHttp\json_decode($response->getBody(), true);
                $errors['data'] = [];
                return $errors;
            }
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $errors = [];
            $errors['code'] = 400;
            $errors['errors'] = array($exception->getMessage());
            $errors['message'] = trans('message.api.error');
            $errors['meta']['code'] = 400;
            $errors['meta']['message'] = trans('message.api.error');
            $errors['meta']['errors'] = array($exception->getMessage());
            $errors['data'] = [];
            return $errors;
        }
    }
}
