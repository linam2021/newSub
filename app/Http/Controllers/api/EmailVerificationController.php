<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\SendMailreset;
use App\Traits\Messenger;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class EmailVerificationController extends Controller
{
    use Messenger;
    public function sendEmail(Request $request)
    {
        if (!$this->validateEmail($request->social_id)) {
            return $this->failedResponse();
        }
        $this->send($request->social_id);
        return $this->successResponse();
    }

    public function send($email)
    {
        $token = $this->createToken($email);
        Mail::to($email)->send(new SendMailreset($token, $email));
    }

    public function createToken($email)
    {
        //$oldToken = DB::table('users')->where('social_id', $email)->first();

        // if ($oldToken) {
        //     return $oldToken->token;
        // }

        $token = Str::random(5);
        $this->saveToken($token, $email);
        return $token;
    }

    public function saveToken($token, $email)
    {
        DB::table('users')->where('social_id' , $email)->update([
            'is_emailVerified' => $token
        ]);
    }

    public function validateEmail($email)
    {
        return !!User::where('social_id', $email)->first();
    }

    public function failedResponse()
    {
        return $this->sendError(
            ['error' => 'Email wasn\'t found in our database']);
    }

    public function successResponse()
    {
        return $this->sendResponse('','Reset Email was sent successfully, please check your inbox.');
    }
}
