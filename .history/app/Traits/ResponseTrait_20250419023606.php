<?php

namespace App\Traits;

trait ResponseTrait
{

    public static function returnError($msgErorr = "", $errorNumber = 400, $meta = []): \Illuminate\Http\JsonResponse
    {

        return response()->json([
            "success" => false,
            "status" => $errorNumber,
            "message" => $msgErorr,
            "meta" => $meta
        ]);

    }
    public static function returnSuccess($msgSuccess = "", $succesNumber = 200, $meta = [])
    {

        return response()->json([
            "success" => true,
            "status" => $succesNumber,
            "message" => $msgSuccess,
            "meta" => $meta
        ]);

    }

    public static function returnData($msgData = "", $data = [], $responseNumber = 200, $meta = null)
    {

        if (!is_null($meta)) {
            return response()->json([
                "success" => true,
                "status" => $responseNumber,
                "message" => $msgData,
                "data" => $data,
                "meta" => [
                    'total' => $meta[0]->total() + $meta[1]->total(),
                    'per_page' => $meta[0]>perPage(),
                    'current_page' => $meta->currentPage(),
                    'last_page' => $meta->lastPage(),
                    'from' => (($meta->currentPage() - 1) * $meta->perPage()) + 1,
                    'to' => min($meta->total(), $meta->currentPage() * $meta->perPage()),
                ]
            ]);
        } else {
            return response()->json([
                "success" => true,
                "status" => $responseNumber,
                "message" => $msgData,
                "data" => $data,
                "meta" => []
            ]);
        }
    }

}
