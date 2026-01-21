<?php

//app/Http/Controllers/Api/V1/WalletController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Custom\FormatResponseController;
use App\Models\Transaction;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Payment Processor Version 0.0.1', weight: 1)]

/**
 * @group Payment Processor Version 0.0.1
 *
 */

class WalletController extends Controller
{
    /**
     * GetBalance
     *
     * Retrieves merchants wallet balance. <br> - Requires the use of either your <b>public key</b> or <b>secret key</b>.
     *
     */

    public function getWalletBalance()
    {

        try {

            $user = auth()->user();

            $data = [
                'status'         => 'successful',
                'wallet_balance' => $user->balance,
                'currency_code'  => 'USD',

            ];

            return FormatResponseController::response200($data);

        } catch (QueryException $e) {
            // Handle query exceptions specifically
            return FormatResponseController::response500($e->getMessage());
        } catch (\Exception $e) {
            // Fallback for catching other exceptions
            return FormatResponseController::response500($e->getMessage());

        }
    }

    /**
     * WithdrawFund
     *
     * Withdraws fund from a merchant wallet balance. <br> - Requires the use of your <b>secret key</b>.
     *
     */

    public function withdrawFund(Request $request)
    {
        // Validate input.
        $request->validate([
            //Amount to be withdrawn. Example: 25000.56
            'amount' => 'required|decimal:2|max:500000',
        ]);

        $user = auth()->user();
        if ($user->balance < $request->amount) {
            return FormatResponseController::response422('Validation error');
        }

        try {
            // Wrap the transaction in a try-catch block.
            DB::transaction(function () use ($request, $user) {

                // Retrieve and lock the associated user record
                User::where('id', $user->id)
                    ->lockForUpdate()
                    ->first();

                $user->decrement('balance', $request->amount);

            });

            // If the transaction succeeds, return a success JSON response.
            $data = [

                'status'           => 'successful',
                'message'          => 'Withdrawal completed successfully!',
                'amount_withdrawn' => $request->amount,
                'balance'          => number_format($user->balance, 2, '.', ''),
            ];
            return FormatResponseController::response200($data);

        } catch (QueryException $e) {
            // Handle query exceptions specifically
            return FormatResponseController::response500($e->getMessage());

        } catch (\Exception $e) {

            return FormatResponseController::response500($e->getMessage());

        }

    }

}

