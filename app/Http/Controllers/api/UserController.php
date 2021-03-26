<?php

namespace App\Http\Controllers\api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\api\EmailVerificationController;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Carbon;

/*
 *This is the controller for each account in the database (users table).
 *Each controller function represents a specific function in the database.
 *Note: You can visit the .env file for more data about the database.
 *Functions:

    *register:{
        The registration will allow the program that works through the API ,
        using email and password.
    }

    *login :{
        Logging in is done with the email address and password, and if the information sent is correct,
        a token will be returned as a value in the response,
        Which allows the user to perform the rest of the operations that need authentication with a token
    }

    *updateUser:{
        In updating the user, the update values are received in the function from these values the user ID,
        which allows the program to identify the account that should be updated in the database.
    }

    *deleteUser:{
        Simply delete a user The user to be deleted is specified using the ID received by the function,
        and the deletion takes place in the database.
    }

    *searchForUser:{
        The search function on the user works with the same mechanism as the previous function,
        but it receives the ID of the receiver as a parameter in the request.
    }
*/

class UserController extends Controller
{
    use GeneralTrait;

    public function register(Request $request)
    {
        $rules = [
            'social_id' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|max:255',
            'c_password' => 'required|string|min:6|max:255|same:password',
        ];

        $validation = validator()->make($request->all(), $rules);

        if ($validation->fails()) {
            return $this->returnValidationError($validation);
        } else {
            $inputs = $request->input();
            try {
                $date =Carbon::now()->addHour();
                if ($inputs['social_id']=='thegameoflife.tg@gmail.com')
                {
                    $result = DB::table('users')->insert([
                    'display_name' => null,
                    'social_id' => $inputs['social_id'],
                    'password' => Hash::make($inputs['password']),
                    'role' => 'owner',
                    'is_banned' => 'false',
                    'created_at' => $date,
                    'updated_at' => $date,
                    ]);
                }
                else
                {
                    $result = DB::table('users')->insert([
                    'display_name' => null,
                    'social_id' => $inputs['social_id'],
                    'password' => Hash::make($inputs['password']),
                    'role' => 'hero',
                    'is_banned' => 'false',
                    'created_at' => $date,
                    'updated_at' => $date,
                    ]);
                }
                $userVerifyedEmail=new EmailVerificationController();
                $request1 = new Request([
                       'social_id'   => $inputs['social_id']
                    ]);
                $userVerifyedEmail->sendEmail($request1);
                return $this->returnData('register', $result, "User is registered Successfully!");
            } catch (\Throwable $th) {
                $msg=$th->getMessage();
                if (Str::contains($msg, 'users_social_id_unique'))
                    return $this->returnError("400", 'Duplicate email');
                else
                   return $this->returnError("", $th->getMessage());
            }
        }
    }

    public function  verifyEmail(Request $request)
    {
        try {
            $credentials = $request->all();
            $validator = Validator::make($credentials,[
                'social_id' => 'required|email',
                'verifyCode' => 'required|string',
            ]);

            if($validator->fails())
            {
                return $this->returnValidationError($validator);
            }
            $inputs = $request->input();
            $user=DB::table('users')->where('social_id' , $inputs['social_id'])->first();

            if ($user->is_emailVerified == '1')// strcmp($user->is_emailVerified,'1') == 1)
                 return $this->returnError("400", 'The email was verified previously');
            if(strcmp($user->is_emailVerified,$inputs['verifyCode']) != 0)
                return $this->returnError("400", 'The Email Verification is failed, Enter correct verification code');
            else
            {
                $user=DB::table('users')->where('social_id' , $inputs['social_id'])->update([
                    'is_emailVerified' => '1'
                ]);
                return $this->returnData('VerifyEmail', True, 'The Email is verified successfully');
            }

        } catch (\Throwable $th) {
            return $this->returnError('E001', $th->getMessage());
        }
    }

    public function resendVerifyCode(Request $request)
    {
        try {
            $credentials = $request->all();
            $validator = Validator::make($credentials,[
                'social_id' => 'required|email'
            ]);

            if($validator->fails())
            {
                return $this->returnValidationError($validator);
            }
            $inputs = $request->input();
            $user=DB::table('users')->where('social_id' , $inputs['social_id'])->first();
            if ($user==null)
                return $this->returnError('400',"The email is not found");
            else
            {
                if (strcmp($user->is_emailVerified,'1') != 0)
                {
                   $userVerifyedEmail=new EmailVerificationController();
                   $request1 = new Request([
                    'social_id'   => $inputs['social_id']
                   ]);
                   $userVerifyedEmail->sendEmail($request1);
                }
                else
                  return $this->returnError('400',"The email was verified previously");
            }
            return $this->returnData('VerifyEmail', True, 'The verified code is resend successfully');
        } catch (\Throwable $th) {
            return $this->returnError('E001', $th->getMessage());
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->all();
            $validator = Validator::make($credentials,[
                'social_id' => 'required|email',
                'password' => 'required|string',
            ]);

            if($validator->fails())
            {
                return $this->returnValidationError($validator);
            }
            if (! $token = Auth::attempt($credentials)) {
                return $this->returnError('401','Incorrect email or password');
            }
            $userId = Auth::id();
            $user = User::where('id',$userId)->first();
            $user->token = $token;
            if ($user->is_banned=="true")
                return $this->returnError('401','This email is banned');
            if ($user-> is_emailVerified !='1')
               return $this->returnError('401','You must verify your account');
            return $this->returnData('Data', $user, 'The response was successful');
        } catch (\Throwable $th) {
            return $this->returnError('E001', $th->getMessage());
        }
    }

    public function logout()
    {
        try{
            Auth::logout();
            return $this->returnData('logout', 'successful', "User was logged out Successfully!");
        }
        catch(\Throwable $th){
            return $this->returnError("", $th->getMessage());
        }
    }

    public function updateUser(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
            //'display_name' => 'string|max:255',
            'role' => 'string|in:hero,admin',
            'is_banned' => 'string|in:true,false',
        ];
        $inputs = $request->all();
        $validation = validator()->make($inputs, $rules);

        if ($validation->fails()) {
            return $this->returnValidationError($validation);
        } else {
            try {

                $AdminId=Auth::id();
                $AdminUser = DB::table('users')->where('id' , $AdminId)->first();
                if ($AdminUser->role =='hero')
                    return $this->returnError('400', 'You do not have privileges to update!');

                $user = DB::table('users')->where('id', $inputs['user_id'])->where('role','!=','owner')->get();
                if (count($user) == 0)
                    return $this->returnError('400', 'user is not found !!');

                $result = DB::table('users')->where('id' , $inputs['user_id'])->update([
                    'role' => $inputs['role'],
                    'is_banned' => $inputs['is_banned'],
                    ]);
                if($inputs['is_banned'] == 'true')
                    DB::table('challenges')->where('user_id',$inputs['user_id'])->delete();

                return $this->returnData('update', $result,"User is updated Successfully!");
            } catch (\Throwable $th) {
                return $this->returnError("", $th->getMessage());
            }
        }
    }

    public function addPicture(Request $request)
    {
        $rules = [
            'pictureName' => 'string',
        ];
        $inputs = $request->all();
        $validation = validator()->make($inputs, $rules);

        if ($validation->fails()) {
            return $this->returnValidationError($validation);
        } else {
            try {
                $userId = Auth::id();
                $result=DB::table('users')->where('id' , $userId)->update([
                    'pictureName' => $inputs['pictureName'],
                    ]);

                return $this->returnData('update', $result,"User picture is updated Successfully!");
            } catch (\Throwable $th) {
                return $this->returnError("", $th->getMessage());
            }
        }
    }


    public function updateName(Request $request)
    {
        $rules = [
            'display_name' => 'string',
        ];
        $inputs = $request->all();
        $validation = validator()->make($inputs, $rules);

        if ($validation->fails()) {
            return $this->returnValidationError($validation);
        } else {
            try {
                $userId = Auth::id();
                $result=DB::table('users')->where('id' , $userId)->update([
                    'display_name' => $inputs['display_name'],
                    ]);

                return $this->returnData('update', $result,"User Name is updated Successfully!");
            } catch (\Throwable $th) {
                return $this->returnError("", $th->getMessage());
            }
        }
    }

    public function getUserPictureName()
    {
        try {
                $userId = Auth::id();
                $result=DB::table('users')->where('id' , $userId)->first();
                return $this->returnData('picturename', $result->pictureName,"User picture is retrieved!");
            } catch (\Throwable $th) {
                return $this->returnError("", $th->getMessage());
            }
    }

    public function deleteUser(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
        ];

        $validation = validator()->make($request->all(), $rules);

        if ($validation->fails()) {
            return $this->returnValidationError($validation);
        } else {

            $AdminId=Auth::id();
            $AdminUser = DB::table('users')->where('id' , $AdminId)->first();
            if ($AdminUser->role =='hero')
                return $this->returnError('400', 'You do not have privileges to delete!');

            $inputs = $request->input();
            try {
                $deletion = DB::table('users')->where('id' , $inputs['user_id'])->where('role','!=','owner')->delete();

                if (!$deletion) {
                    return $this->returnError('400', 'User is not found!');
                }

                return $this->returnData('deletion', $deletion, "User is deleted Successfully!");
            } catch (\Throwable $th) {
                return $this->returnError('', $th->getMessage());
            }
        }
    }

    public function searchForUser(Request $request)
    {
        $rules = [
            'display_name' => 'string',
            // 'with_social_id' => 'required|boolean',
            //'social_id' => 'string',
        ];

        $validation = validator()->make($request->all(), $rules);

        if ($validation->fails()) {
            return $this->returnValidationError($validation);
        } else {
            $inputs = $request->input();
            try {
                // if($inputs['with_social_id']){
                //     $users = DB::table('users')->where('social_id', $inputs['social_id'])->get();
                // }
                // else{
                //      $users = DB::table('users')->where('display_name', 'like', '%' . $inputs['display_name'] . '%')->get();
                // }

                $users = User::where('display_name', 'like', '%' . $inputs['display_name'] . '%')->get();

                if (!$users) {
                    return $this->returnError('400', 'User not found');
                }

                return $this->returnData('users', $users, "The response was successful");
            } catch (\Throwable $th) {
                return $this->returnError('', $th->getMessage());
            }
        }
    }
}
