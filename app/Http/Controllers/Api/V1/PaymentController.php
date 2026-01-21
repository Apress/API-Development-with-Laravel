<?php

//app/Http/Controllers/Api/V1/PaymentController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Custom\FormatResponseController;
use App\Jobs\SendWebhookNotification;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Group('Payment Processor Version 0.0.1', weight: 3)]
/**
 * @group Payment Processor Version 0.0.1
 *
 */
class PaymentController extends Controller
{
    /**
     * PaymentCharge
     *
     * It process payment and dispatch jobs for webhook, email, etc to relay the status of the payment processed.
     *
     * @unauthenticated
     *
     */
    public function processPayment(Request $request)
    {
        // Validate input.
        $request->validate([
            //Card holder's name. Example: ENIOLUWA MIKE STEVEN
            'card_name'       => 'required|string',

            //Card number. Example: 5453 8765 7654 5432
            'card_num'        => 'required|string|min:12|max:19',

            //Card expiry date. Example: 12/99
            'card_exp'        => 'required|string|size:5',

            //Card cvc. Example: 906
            'card_secret'     => 'required|string|min:3|max:4',

            //Amount to pay. Example: 10890.75
            'amount'          => 'required|decimal:2|max:1000000000|min:0.5',

            //Three letter currency code. Examples - USD
            'currency_code'   => 'required|string|size:3',

            //Transaction reference id. Example: f15ee5cd-dd0d-4ec9-8e39-922708d10f99
            'transaction_ref' => 'required|uuid',

            //A value of false means you are paying via the provided checkout_url, which when visited displays a form provided by Dexy Pay for payment to be made, while a value of true means you've retrieved customer's card details yourself and you are now forwarding it with the data obtained from calling the initialization endpoint earlier for a direct charge/payment of your customer. <br><br>Example: 1
            'direct_charge'   => 'required|boolean',

            //Payment method (optional). Example: card
            'payment_method'  => 'string|min:1|max:10',

        ]);

        log::info('inside processPayment');

        $direct_charge = $request->direct_charge;
        $amount        = $request->amount;
        $tx_id         = $request->transaction_ref;

        //verify existence and validity of transaction request
        $txn = Transaction::where('transaction_ref', $tx_id)->first();

        if (
            ! $txn
            || $amount !== $txn->amount
            || $txn->status === 'completed') {

            if (isset($direct_charge) && ! $direct_charge) {
                             log::info('ifErr: '.$amount.' '.$txn->status);

                return FormatResponseController::responseWeb('failed');
            } else {

                return FormatResponseController::response422('Validation error***1');

            }

        }

        // Retrieve and lock the associated user record
        $user = User::where('id', $txn->user_id)
            ->lockForUpdate()
            ->first();

        try {

            // Wrap the transaction in a try-catch block.
            DB::transaction(function () use ($request, $txn, $user) {

                //Amount due to merchant after deducting payment fee
                $netPay = $txn->amount - $txn->fee;
                //Credit the merchant
                $user->increment('balance', $netPay);
                //Update transaction status to completed
                $this->recordPaymentStatus($request, $txn);

                //set transaction status to completed
                $txn->status = 'completed';
                $txn->save();

            });



            if (
                isset($direct_charge) && ! $direct_charge) {

                $this->sendMail($txn);
                $this->sendWebhookNotification($user);
                return FormatResponseController::responseWeb('successful', $txn->redirect_url);
            } else {

                $this->sendMail($txn);
                $this->sendWebhookNotification($user);
                // If the transaction succeeds, return a success JSON response.
                return FormatResponseController::response200($this->successData($txn));
            }

        } catch (QueryException $e) {

            if (
                isset($direct_charge) && ! $direct_charge) {

                return FormatResponseController::responseWeb('failed');
            } else {

                return FormatResponseController::response500($e->getMessage() . '***2');

            }

        } catch (\Exception $e) {

            if (isset($direct_charge) && ! $direct_charge) {

                return FormatResponseController::responseWeb('failed');
            } else {

                return FormatResponseController::response500('Transaction failed: ' . $e->getMessage() . '***3');
            }

        }



    }

    /**
     * @hideFromAPIDocumentation
     */

    private function recordPaymentStatus(Request $request, $txn)
    {
        Payment::create([
            'transaction_id' => $txn->transaction_ref,
            'payment_method'  => $request->payment_method,
            'payment_status'  => $txn->status,
        ]);
    }

    private function successData($txn)
    {

        return [
            'status'          => 'success',
            'message'         => 'Transaction completed successfully!',
            'amount'          => $txn->amount,
            'fee'             => $txn->fee,
            'amount_less_fee' => number_format($txn->amount - $txn->fee, 2, '.', ''),
            'transaction_ref' => $txn->transaction_ref,
        ];

    }

    private function sendMail($txnn){

            $subject = 'Payment Receipt Notification';

            $mailData = [
                'mailView' => 'emails.success-customer',
                'subject'  => $subject,
                'amount'   => $txnn->amount,
                'refid'    => $txnn->transaction_ref,
                'email'    => $txnn->email,

            ];

            Mail::to($txnn->email)->send(new \App\Mail\PaymentMail($mailData));

            $mailData['subject'] = 'Payment Notification';

            $mailData['mailView'] = 'emails.success-merchant';

            $mailData['email']    = $txnn->user->email;

            Mail::to($txnn->user->email)->send(new \App\Mail\PaymentMail($mailData));



    }

    private function sendWebhookNotification($user){

            // Dispatch a job for the user's webhook URL using their secret
           /* SendWebhookNotification::dispatch(
                $user->webhook_url,
                $user->webhook_secret,
                $this->successData($txn)
            );*/

    }
}
