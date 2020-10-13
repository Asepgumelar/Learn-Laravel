<?php


namespace App\Http\Controllers\Api;


use App\Models\Disbursement;
use App\Models\DisbursementCallback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisbursementController
{

    public function disbursementCallback(Request $request)
    {
        $inputAll = $request->all();
        DB::transaction(function () use ($inputAll) {
            DB::table('data_logs')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'log_text' => json_encode($inputAll),
                'log_type' => 'input',
                'module' => 'disbursement',
                'method' => 'disbursementCallback',
                'created_at' => now()
            ]);
        });

        try {
            DB::beginTransaction();

            $disbursement = Disbursement::query()->where('external_id', '=', $request->external_id)->first();
            if (!$disbursement) {
                throw new \Exception("Disbursement with external id " . $request->external_id . ' is not found');
            }
            $data = DisbursementCallback::query()->create([
                "disbursement_id" => $disbursement->id,
                "xendit_id" => $request->id,
                "xendit_user_id" => $request->user_id,
                "external_id" => $request->external_id,
                "amount" => $request->amount,
                "bank_code" => $request->bank_code,
                "account_holder_name" => $request->account_holder_name,
                "disbursement_description" => $request->disbursement_description,
                "failure_code" => $request->failure_code,
                "is_instant" => $request->is_instant,
                "status" => $request->status,
            ]);

            $disbursement->status = $data->status;
            $disbursement->update();
            DB::commit();
            return response(array('message' => 'Success'), JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            DB::transaction(function () use ($e) {
                DB::table('data_logs')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'log_text' => json_encode($e->getMessage()),
                    'log_type' => 'error',
                    'module' => 'disbursement',
                    'method' => 'disbursementCallback',
                    'created_at' => now()
                ]);
            });
            return response(array('message' => 'Error', 'error' => $e->getMessage()), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
