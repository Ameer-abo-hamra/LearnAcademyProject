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
        // التعامل مع البيانات سواء كانت مصفوفة أو عنصر واحد
        if (!is_array($meta)) {
            $meta = [$data]; // إذا كانت قيمة واحدة، حولها إلى مصفوفة
        }

        // التعامل مع الميتا إذا كانت مصفوفة وتحتوي على بياناتpagination
        if (is_array($meta) && count($meta) === 2 && $meta[0] && $meta[1]) {
            return response()->json([
                "success" => true,
                "status" => $responseNumber,
                "message" => $msgData,
                "data" => $data,
                "meta" => [
                    'total' => $meta[0]->total() + $meta[1]->total(),
                    'per_page' => $meta[0]->perPage(),
                    'current_page' => $meta[0]->currentPage(),
                    'last_page' => $meta[0]->lastPage(),
                    'from' => ($meta[0]->currentPage() - 1) * $meta[0]->perPage() + 1,
                    'to' => min($meta[0]->total() + $meta[1]->total(), $meta[0]->currentPage() * $meta[0]->perPage()),
                ]
            ]);
        }
        if (is_array($meta) && count($meta) === 1 && $meta[0] ) {
            return response()->json([
                "success" => true,
                "status" => $responseNumber,
                "message" => $msgData,
                "data" => $data,
                "meta" => [
                    'total' => $meta[0]->total(),
                    'per_page' => $meta[0]->perPage(),
                    'current_page' => $meta[0]->currentPage(),
                    'last_page' => $meta[0]->lastPage(),
                    'from' => ($meta[0]->currentPage() - 1) * $meta[0]->perPage() + 1,
                    'to' => min($meta[0]->total(), $meta[0]->currentPage() * $meta[0]->perPage()),
                ]
            ]);
        }

        // استجابة إذا لم يكن هناك ميتا أو لم تكن هناك pagination
        return response()->json([
            "success" => true,
            "status" => $responseNumber,
            "message" => $msgData,
            "data" => $data,
            "meta" => []
        ]);
    }



}
