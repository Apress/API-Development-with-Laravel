<?php

//app/Http/Controllers/Custom/FormatResponseController.php

namespace App\Http\Controllers\Custom;

use Illuminate\Support\Facades\Log;

class FormatResponseController
{
    public static function response500($msg = 'Server error')
    {
        // Handle query exceptions specifically
        Log::error("Server error: " . $msg);
        return response()->json([
            'status'  => 500,
            'message' => $msg,
        ], 500);

    }
    public static function response422($err)
    {
        Log::error("validation errors " . $err);

        // Customize the error handling
        return response()->json([
            'status'  => 422,
            'message' => 'There were validation errors',
            'errors'  => $err,
        ], 422);
    }

    public static function response401($arr = [])
    {
        Log::error("Failed authentication " . $err);

        // Customize the error handling
        return response()->json([
            'status'  => 401,
            'message' => 'Unauthenticated',
            'errors'  => $err,
        ], 401);

    }

    public static function response403($arr)
    {
        Log::error("Permission denied: " . $err);

        // Customize the error handling
        return response()->json(
            ['status' => 403,
                'message' => 'Unauthenticated',
                'errors'  => $err,
            ],
            401
        );

    }

    public static function response200($arr = [])
    {
        return response()->json(
            ["status" => 200,
                "success" => true,
                "message" => "Success",
                "data"    => $arr,
            ],
            200
        );

    }
    public static function responseWeb($sts, $redir_url = '')
    {
        $status       = $sts;
        $redirect_url = $redir_url;
        //$payment_link = $pay_link;
        return view(
            'payment.status',
            compact(
                'status',
                'redirect_url'
            )
        );

    }
}
