<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Odenktools\Bca\Facades\Bca;

class BcaController extends Controller
{

    public function loginBca()
    {
        try {
            $response = Bca::httpAuth();
            Log::info($response);
            return response()->json($response);
        }

        catch (Exception $e) {
            Log::error($e->getMessage());
            return response([
                'message' => 'Error',
                'error' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getBalance(Request $request)
    {
        try {
            $token          = "MvXPqa5bQs5U09Bbn8uejBE79BjI3NNCwXrtMnjdu52heeZmw9oXgB";
            $arrayAccNumber = ['0201245680', '0063001004', '1111111111'];
            $response       = Bca::getBalanceInfo($token, $arrayAccNumber);
            Log::info($response);
            return response()->json($response);
        }

        catch (Exception $e) {
            Log::error($e->getMessage());
            return response([
                'message' => 'Error',
                'error' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function postTransfer(Request $request)
    {
        try {
            $token            = "MvXPqa5bQs5U09Bbn8uejBE79BjI3NNCwXrtMnjdu52heeZmw9oXgB"; // Nilai token yang dihasilkan saat login
            $amount           = '50000.00';                  // Nilai akun bank anda
            $nomorakun        = '0201245680';                // Nilai akun bank yang akan ditransfer
            $nomordestinasi   = '0201245681';                // Nomor PO, silahkan sesuaikan
            $nomorPO          = '12345/PO/2017';             // Nomor Transaksi anda, Silahkan generate sesuai kebutuhan anda
            $nomorTransaksiID = '00000001';
            $response         = Bca::fundTransfers($token,
                                $amount,
                                $nomorakun,
                                $nomordestinasi,
                                $nomorPO,
                                'Asep Coba Transfer',
                                'Online Saja Ko',
                                $nomorTransaksiID);
            Log::info($response);

            return response()->json($response);
        }

        catch (Exception $e) {
            Log::error($e->getMessage());
            return response([
                'message' => 'Error',
                'error' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
