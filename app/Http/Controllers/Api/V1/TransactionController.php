<?php

//app/Http/Controllers/Api/V1/TransactionController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Custom\FormatResponseController;
use App\Models\Transaction;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Group('Payment Processor Version 0.0.1', weight: 2)]

class TransactionController extends Controller
{

    /**
     * InitPayment
     *
     * It initializes payment in preparation for charging a customer.<br> -Requires the use of your <b>secret key</b>.
     *
     */

    public function initTransaction(Request $request)
    {
        $user = auth()->user();

        try {
            // Validate input.
            $request->validate([
                //The customer's name. Optional. Example: Peter Parker Smith

                'name'          => 'string',

                //Customer's email. Required. Example: ppsmith@mail.com
                'email'         => 'required|email',

                //Amount to be paid. Required. Example: 55678.55
                'amount'        => 'required|decimal:2|min:0.50|max:1000000000.00',

                //Currency of payment. Required. Only USD accepted for now. Example: USD
                'currency'      => 'required|string|size:3',

                //The URL to redirect customer to once payment is done. Example: https://myapp.com
                'redirect_url'  => 'url:http',

                //Determines who pay, custer or merchant, the payment processing charges. A value of 1 means customer pay. 0 means merchant pay. Example: 1
                'pass_charge'   => 'boolean',

                //Is payment a direct charge?. A value of 0 (false) means you are paying via the URL (<em>checkout_url</em>) receieved after calling this endpoint for payment initialization, this URL when visited displays a form provided by Dexy Pay for payment to be made, while a value of 1 (true) means you are retrieving customer's card details yourself and forwarding it with the data returned by this endpoint to the <em>/payment/charge</em> endpoint for a direct charge of the customer. <br> In the first case, you are using Dexy Pay's provided payment page to collect the customer's card details; while in the last case, you are responsible for devicing a means for collecting the card detail yourself, say via a form on your website or app.<br><br>Example: false
                'direct_charge' => 'boolean',

            ]);
        } catch (ValidationException $e) {
            return FormatResponseController::response422($e->errors() . '1**');
        }

        try {

            $transaction_ref = Str::uuid();
            $amount          = $request->amount;
            //create the payment link
            $payLink = $this->getPaymentLink($transaction_ref, $amount);
            //
            $payFee = $this->getPaymentFee($amount);
            //register transaction
            $transaction = Transaction::create([
                'user_id'         => $user->id,
                'transaction_ref' => $transaction_ref,
                'amount'          => $amount,
                'fee'             => $payFee,
                'email'           => $request->email,
                'currency'        => $request->currency,
                'redirect_url'    => $request->redirect_url,
                'checkout_url'    => $payLink,
                'pass_charge'     => $request->pass_charge,
                'status'          => 'pending',
            ]);

        } catch (QueryException $e) {

            return FormatResponseController::response500($e->getMessage() . '2**');

        } catch (\Exception $e) {
            return FormatResponseController::response500($e->getMessage() . '3**');

        }

        // If the transaction initiation succeeds, return a success JSON response.

        $data = [
            //Success message. <br><br>Example: 'Payment Initialized successfully!'
            'message'            => 'Payment Initialized successfully!',

            //Currency of payment. Only USD accepted for now. <br><br>Example: USD
            'currency'           => 'USD',

            //The URL to redirect customer to once payment is done. <br><br>Example: https://myapp.com
            'redirect_url'       => $request->redirect_url,

            //Transaction reference. Unique to each transaction. <br><br>Example: er43rft-8uytrettu-mjkuyt-bhgtrdsp
            'transaction_ref'    => $transaction->transaction_ref,

            //Amount to be paid. <br><br>Example: 55678.55
            'transaction_amount' => $request->amount,

            //Generated payment link. Used for indirect payment. When visited, displays a payment page.
            'checkout_url'       => $payLink,

            //Transaction status. It can be any of the following: pending, failed, or completed. <br><br>Example: completed
            'status'             => 'successful',

        ];

        return FormatResponseController::response200($data);

    }

    /**
     * GetTransactions
     *
     * Retrieves paginated transactions for a user without the need for the user id.<br> -Requires the use of either your <b>public key</b> or <b>secret key</b>.
     *
     * @response LengthAwarePaginator<Transaction>
     *
     */
    #[QueryParameter('page', description: 'Current page.', type: 'int', default:1, example: 7)]
    public function getTransactions()
    {
        $user = auth()->user();

        $transactions = Transaction::where('user_id', $user->id)->paginate(10);
        return response()->json($transactions);

    }

    /**
     * GetTransaction
     *
     * Retrieves a transaction given a transaction ID.<br> -Replace <b>txnid</b> in the endpoint URL with the ID of the transaction to be retrieved. <br>-Requires the use of either your <b>public key</b> or <b>secret key</b>.
     *
     * @param  string  $txnid  The transaction reference/ID of the transaction to be retrieved. <br><br>Example: f15ee5cd-dd0d-4ec9-8e39-922708d10f99
     *
     */

    public function getTransaction(string $txnid)
    {
        try {

            $user = auth()->user();

            $transaction = Transaction::where('transaction_ref', $txnid)->where('user_id', $user->id)->first();

            //return $transaction;
            $data = [
                //Amount paid
                "transaction_amount"     => $transaction->amount,

                //Transaction reference ID
                "transaction_ref"        => $transaction->transaction_ref,

                //Transaction status. It can be any of the following: pending, failed, or completed. <br><br>Example: completed
                "transaction_status"     => $transaction->status,

                //Payment currency. Only USD is supported
                "transaction_currency"   => $transaction->currency,

                "transaction_created_at" => $transaction->created_at,

                //Customer's email
                "email"                  => $transaction->email,
            ];

            return FormatResponseController::response200($data);

        } catch (QueryException $e) {

            return FormatResponseController::response500($e->getMessage() . '***1');

        } catch (\Exception $e) {

            return FormatResponseController::response500($e->getMessage() . '***2');

        }

    }

    /**
     * VerifyTransaction
     *
     * Verifies a transaction given a transaction ID. <br>-Replace <b>txnid</b> in the endpoint URL with the ID of the transaction to be verified. <br> -Requires the use of either your <b>public key</b> or <b>secret key</b>.
     *
     * @param  string  $txnid  The transaction reference/ID of the transaction to be verified. <br><br>Example: f15ee5cd-dd0d-4ec9-8e39-922708d10f99
     *
     */

    public function verifyTransaction(string $txnid)
    {
        return $this->getTransaction($txnid);

    }

    /**
     * @hideFromAPIDocumentation
     */
    private function getPaymentLink(string $tx_id, string $amt)
    {

        $expiresAt = Carbon::now()->addMinutes(600); // Link valid for 6hours

        // Generate and return a payment link
        return URL::temporarySignedRoute(
            'payment.form',
            $expiresAt,
            ['amt' => $amt, 'tx_ref' => $tx_id]
        );
    }

    /**
     * @hideFromAPIDocumentation
     */

    private function getPaymentFee($amount)
    {
        // Define the fee (flat $0.25 + 0.5%)
        return round(0.25 + ($amount * 0.005), 2);
    }

}
