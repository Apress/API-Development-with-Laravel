<?php

//app/Http/Controllers/Custom/AuthorizeAccessController.php

namespace App\Http\Controllers\Custom;

use App\Models\Transaction;

class AuthorizeAccessController
{
    //
    public static function checkAccessRightToTransaction(
        $user_id,
        $txn_ref) {

        if (Transaction::where('user_id', $user_id)->where('transaction_ref', $txn_ref)->Exists()) {
            //
            return true;
        }
        return false;

    }
}
