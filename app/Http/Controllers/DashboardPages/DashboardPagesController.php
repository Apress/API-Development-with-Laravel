<?php
namespace App\Http\Controllers\DashboardPages;

use App\Http\Controllers\Controller;
use App\Models\HiToken;
use App\Models\Transaction;
use App\Models\BankDetail;
use App\Models\Withdrawal;
use App\Models\Setting;

//use App\Models\User;
use Illuminate\Http\Request;

class DashboardPagesController extends Controller
{
    //
    public function index(Request $request, $page, ?string $action = '')
    {

        \Log::info('amt2withdraw : '.$request->amt2withdraw);

        $request->validate([

            //IBAN code
            'iban'  => 'alpha_num|min:8|max:34',

            //SWIFT Code
            'swift'  => 'alpha_num|min:8|max:11',

            'amt2withdraw'  => 'decimal:2|max:1000000000.00|min:20.00',

            'webhook_url' =>  'url',

            'webhook_secret' => 'string',

        ]);

        $this->request = $request;
        $dPage         = '';
        $data          = [];
        if ($page == 'transactions') {
            $data = $this->transactions($action);
        } elseif ($page == 'tokens') {
            $data = $this->tokens($action, $page);
        } elseif ($page == 'bank') {
            $data = $this->bank($action);
        } elseif ($page == 'withdrawal') {
            $data = $this->withdrawal($action);
        } elseif ($page == 'settings') {
            $data = $this->settings($action);
        }
        $arr   = ['user' => auth()->user()->id, 'page' => $page];
        $sign  = 'Logout';
        $name  = auth()->user()->email;
        $url   = route('auth.logout');
        $paged = ['name' => $page, 'data' => $data];

        return view('dashboard.home', compact('sign', 'name', 'url', 'paged'));
    }

    private function transactions($action)
    {
        $txns = auth()->user()->transaction();
        $txns = $txns->simplePaginate(30)->withPath('/home/pages/transactions/get'); // Get 30 transactions per page

        return ['txns' => $txns, 'cnt' => 0, 'bal' => auth()->user()->balance, 'currency' => 'USD'];

    }

    private function tokens($act, $pg)
    {
        $userid = auth()->user()->id;

        if ($act === 'regen' && $this->request->isMethod('post')) {

            //delete old tokens
            auth()->user()->tokens()->delete();

            //delete existing encrypted tokens
            HiToken::where('user_id', $userid)->delete();

            //generate array of new tokens
            $newToken = $this->generateToken($userid);

            //return the new tokens
            return $newToken;
        }

        //Try to reteieve user's tokens
        $retToken = HiToken::firstWhere('user_id', $userid);

        //if the user already has token in the database
        if ($retToken) {
            //return retrieved tokens
            return ['public' => $retToken->p_token, 'secret' => $retToken->s_token];

        } else {

            //generate array of new tokens
            $newToken = $this->generateToken($userid);

            //return the new tokens
            return $newToken;
        }

    }

    private function bank($act)
    {

        \Log::info("bank: " . $this->request->iban.' and  '.$this->request->swift);


        $uid = auth()->user()->id;

        if($act === "post" && $this->request->iban && $this->request->swift){
             //Store the user bank details in database
             BankDetail::updateOrCreate(
             [
                'user_id' => $uid,
             ],
             [
                'iban' => $this->request->iban,
                'swift' => $this->request->swift,
             ]);

             return ['iban' => $this->request->iban, 'swift' => $this->request->swift];
        }

        //Reteieve user's bank details
        $bankDetails = BankDetail::firstWhere('user_id', $uid);

        //if the user already has token in the database
        if ($bankDetails) {
            //return retrieved tokens
            return ['iban' => $bankDetails->iban, 'swift' => $bankDetails->swift];

        }

        return ['iban' => '', 'swift' => ''];
    }

    private function withdrawal($act)
    {

        $balance = (float)auth()->user()->balance;
        $amt2with = (float)$this->request->amt2withdraw;

        \Log::info($balance.' bal > a2w  '.$amt2with);

        if($act === "post" && $this->request->amt2withdraw && $amt2with <= $balance){

             //Credit the merchant
             auth()->user()->decrement('balance', $amt2with);

             //record withdrawal
             $this->recordWithdrawal();

        }


        $withdrawals = auth()->user()->withdrawal();
        $withdrawals = $withdrawals->simplePaginate(30)->withPath('/home/pages/withdrawal/get'); // Get 30 withdrawals per page

        return ['withdrawals' => $withdrawals, 'cnt' => 0, 'bal' => auth()->user()->balance, 'currency' => 'USD'];


    }

    private function settings($act)
    {
        \Log::info("settings: " . $this->request->webhook_url.' and  '.$this->request->webhook_secret);


        $uid = auth()->user()->id;

        if($act === "post" && ($this->request->webhook_url || $this->request->webhook_secret)){
             //Store the user bank details in database
             Setting::updateOrCreate(
             [
                'user_id' => $uid,
             ],
             [
                'webhook_url' => $this->request->webhook_url,
                'webhook_secret' => $this->request->webhook_secret,
             ]);

             return ['webhook_url' => $this->request->webhook_url, 'webhook_secret' => $this->request->webhook_secret];
        }

        //Reteieve user's bank details
        $settings = Setting::firstWhere('user_id', $uid);

        //if the user already has token in the database
        if ($settings) {
            //return retrieved tokens
            return ['webhook_url' => $settings->webhook_url, 'webhook_secret' => $settings->webhook_secret];

        }

        return ['webhook_url' => '', 'webhook_secret' => ''];
    }

    private function generateToken(int $uid)
    {
        //generate new tokens
        $p_token = auth()->user()->createToken(
            'public', ['public']
        );
        $s_token = auth()->user()->createToken(
            'secret', ['secret']
        );

        //Store the encrypted tokens in database
        HiToken::create([
            'user_id' => $uid,
            'p_token' => $p_token->plainTextToken,
            's_token' => $s_token->plainTextToken,
        ]);

        return ['public' => $p_token->plainTextToken, 'secret' => $s_token->plainTextToken];

    }


    private function recordWithdrawal()
    {
        Withdrawal::create([
            'user_id' => auth()->user()->id,
            'amount'  => $this->request->amt2withdraw,
            'status'  => 'successful',
        ]);
    }

}
