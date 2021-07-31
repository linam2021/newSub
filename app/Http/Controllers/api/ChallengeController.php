<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;

use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Carbon;

class ChallengeController extends Controller
{
    use GeneralTrait;

    public function createChallenge(Request $request)
    {
        $userId = Auth::id();
        $credentials = $request->all();
        $validator = Validator::make($credentials,[
            'display_name' => 'required|string|min:4|max:255|unique:users',
            'hero_instagram' => 'required|unique:challenges|string',
            'hero_target' => 'required|string|min:5|max:255',
        ]);

        if($validator->fails())
        {
            return $this->returnValidationError($validator);
        }

       else {
            $date =Carbon::now()->addHour();
            $challenge = DB::table('challenges')->where('user_id', $userId)->get();
            if(count($challenge)>0)
                 return $this->returnError('400', 'one challenge already exists!');
            $inputs = $request->input();


            try {
                DB::table('users')->where('id' , $userId)->update([
                    'display_name' => $inputs['display_name'],
                    ]);
                $user=DB::table('users')->where('id' , $userId)->first();
                if($user->social_id=='thegameoflife.tg@gmail.com')
                    DB::table('challenges')->insert([
                    'hero_instagram' => $inputs['hero_instagram'],
                    'hero_target' => $inputs['hero_target'],
                    'user_id' => $userId,
                    'in_leader_board' => 1,
                    'is_challengVerified'=>'true',
                    'priority'=>1,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
                else
                    DB::table('challenges')->insert([
                    'hero_instagram' => $inputs['hero_instagram'],
                    'hero_target' => $inputs['hero_target'],
                    'user_id' => $userId,
                    'in_leader_board' => 1,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                $challenges = DB::table('challenges')->where('hero_instagram', $inputs['hero_instagram'])->where('hero_target', $inputs['hero_target'])->where('user_id', $userId)->first();

                if (!$challenges) {
                    return $this->returnError('400', 'challenge is not found');
                }

                return $this->returnData('challenge', $challenges,"Challenge is created Successfully!");
            } catch (\Throwable $th) {
                $msg=$th->getMessage();
                if (Str::contains($msg, 'users_display_name_unique'))
                    return $this->returnError("400", 'Duplicate display_name');
                else  if (Str::contains($msg, 'challenges_hero_instagram_unique'))
                    return $this->returnError("400", 'Duplicate instagram account');
                else
                    return $this->returnError('', $th->getMessage());
            }
        }
    }

    public function deleteChallenge(Request $request)
    {
        $rules = [
            'id' => 'required|integer',
            'user_id' => 'required|integer',
        ];

        $validation = validator()->make($request->all(), $rules);

        if (!$validation) {
            return $this->returnValidationError($validation);
        } else {
            $inputs = $request->input();

            try {
                $result = DB::table('challenges')->where('id', $inputs['id'])->where('user_id', $inputs['user_id'])->delete();

                if (!$result) {
                    return $this->returnData('deletion', false);
                }
                return $this->returnData('deletion', true);
            } catch (\Throwable $th) {
                return $this->returnError('', $th->getMessage());
            }
        }
    }

    public function getChallenge()
    {
        $userId=Auth::id();
        try {
            $challenge = DB::table('challenges')->where('user_id', $userId)->first();
            $challenge->role=DB::table('users')->where('id',$userId)->first()->role;

            if (!empty($challenge)) {
                return $this->returnData('challenge', $challenge, "The response was successful");
            } else {
                return $this->returnError('', 'challenge is not found !!');
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function getTrandingChallenges()
    {
       try {
            $challenges= Challenge::select(['challenges.user_id','challenges.hero_instagram','is_challengVerified', 'challenges.points as score'])
            ->where('in_leader_board', 1)->orderByDesc('priority')->orderByDesc('points')->orderBy('created_at')->get();

            foreach($challenges as $challenge)
            {
                $challenge->display_name = DB::table('users')->where('id',$challenge->user_id)->first()->display_name;
                $challenge->pictureName= DB::table('users')->where('id',$challenge->user_id)->first()->pictureName;
                $challenge->role= DB::table('users')->where('id',$challenge->user_id)->first()->role;
            }

            if (!$challenges) {
                return $this->returnError('', 'challenges not found');
            }

            return $this->returnData('challenges', $challenges, "The ternding is in points");
            } catch (\Throwable $th) {
                return $th->getMessage();
            }
    }

    public function getTrandingChallengesbyAvg21day()
    {
        try
        {
            //update avg for all challenges
            $today =Carbon::now()->addHour();
            $dateonly=$today->toDateString();
            $todayTime01=Carbon::createFromFormat('Y-m-d H:i', $dateonly.' 01:01');
            $allChallenge =DB::table('challenges')->get();
            foreach($allChallenge as $ch)
            {
                $created_at= Carbon::createFromFormat('Y-m-d H:i:s' ,$ch->created_at)->toDateString();
                $created_atDateTime0=Carbon::createFromFormat('Y-m-d H:i', $created_at.' 00:00');
                $diff_in_days = ceil($todayTime01->floatDiffInDays($created_atDateTime0));
                DB::table('challenges')->where('user_id', $ch->user_id)->update([
                        'challengeDaysCount'=>$diff_in_days,
                        'average'=>$ch->points/$diff_in_days,
                        ]);
            }
            ///////////////
            $challenges= Challenge::select(['challenges.user_id','challenges.hero_instagram','is_challengVerified', 'challenges.average as score', 'challenges.challengeDaysCount'])
            ->where('in_leader_board', 1)->where('challengeDaysCount','>',21)->orderByDesc('priority')->orderByDesc('score')->orderBy('created_at')->get();
            foreach($challenges as $challenge)
            {
               $challenge->display_name = DB::table('users')->where('id',$challenge->user_id)->first()->display_name;
               $challenge->pictureName= DB::table('users')->where('id',$challenge->user_id)->first()->pictureName;
               $challenge->role= DB::table('users')->where('id',$challenge->user_id)->first()->role;
            }

            if (!$challenges) {
                return $this->returnError('', 'challenges is not found');
            }
            return $this->returnData('challenges', $challenges, "The trending is in average");
        } catch (\Throwable $th) {
                return $th->getMessage();
        }
    }


    public function getTrandingInPoints()
    {
        try
        {
            $appStartDay=Carbon::createFromFormat('d-m-Y H:i:s', '1-8-2021 00:00:00');
            $today = Carbon::now()->addHour();
            $diff_in_days = ceil($today->floatDiffInDays($appStartDay));

            if ($diff_in_days<21)
                return  $this ->getTrandingChallenges();
            else
                return $this->getTrandingChallengesbyAvg21day();

        } catch (\Throwable $th) {
                return $th->getMessage();
        }
    }


    public function getTrandingChallengesPagination()
    {
       try {
            $challenges= Challenge::select(['challenges.user_id','challenges.hero_instagram','is_challengVerified', 'challenges.points as score', 'challenges.challengeDaysCount'])
            ->where('in_leader_board', 1)->orderByDesc('priority')->orderByDesc('score')->orderBy('created_at')->paginate(50);

            foreach($challenges as $challenge)
            {
                $challenge->score =(string) DB::table('challenges')->where('user_id',$challenge->user_id)->first()->points;
                $challenge->display_name = DB::table('users')->where('id',$challenge->user_id)->first()->display_name;
                $challenge->pictureName= DB::table('users')->where('id',$challenge->user_id)->first()->pictureName;
                $challenge->role= DB::table('users')->where('id',$challenge->user_id)->first()->role;
            }

            if (!$challenges) {
                return $this->returnError('', 'challenges not found');
            }

            return $this->returnData('challenges', $challenges, "The trending is in points");
            } catch (\Throwable $th) {
                return $th->getMessage();
            }
    }

    public function getTrandingChallengesbyAvg21dayPagination()
    {
        try
        {
            //update avg for all challenges
            $today =Carbon::now()->addHour();
            $dateonly=$today->toDateString();
            $todayTime01=Carbon::createFromFormat('Y-m-d H:i', $dateonly.' 01:01');
            $allChallenge =DB::table('challenges')->get();
            foreach($allChallenge as $ch)
            {
                $created_at= Carbon::createFromFormat('Y-m-d H:i:s' ,$ch->created_at)->toDateString();
                $created_atDateTime0=Carbon::createFromFormat('Y-m-d H:i', $created_at.' 00:00');
                $diff_in_days = ceil($todayTime01->floatDiffInDays($created_atDateTime0));
                DB::table('challenges')->where('user_id', $ch->user_id)->update([
                        'challengeDaysCount'=>$diff_in_days,
                        'average'=>$ch->points/$diff_in_days,
                        ]);
            }
            ///////////////
            $challenges= Challenge::select(['challenges.user_id','challenges.hero_instagram','is_challengVerified', 'challenges.average as score', 'challenges.challengeDaysCount'])
            ->where('in_leader_board', 1)->where('challengeDaysCount','>',21)->orderByDesc('priority')->orderByDesc('score')->orderBy('created_at')->paginate(50);
            foreach($challenges as $challenge)
            {
               $challenge->score =(string) DB::table('challenges')->where('user_id',$challenge->user_id)->first()->average;
               $challenge->display_name = DB::table('users')->where('id',$challenge->user_id)->first()->display_name;
               $challenge->pictureName= DB::table('users')->where('id',$challenge->user_id)->first()->pictureName;
               $challenge->role= DB::table('users')->where('id',$challenge->user_id)->first()->role;
            }

            if (!$challenges) {
                return $this->returnError('', 'challenges is not found');
            }
            return $this->returnData('challenges', $challenges, "The trending is in average");
        } catch (\Throwable $th) {
                return $th->getMessage();
        }
    }


    public function getTrandingInPointsPagination()
    {
        try
        {
            $appStartDay=Carbon::createFromFormat('d-m-Y H:i:s', '1-8-2021 00:00:00');
            $today = Carbon::now()->addHour();
            $diff_in_days = ceil($today->floatDiffInDays($appStartDay));

            //if ($diff_in_days<21)
            //    return  $this ->getTrandingChallengesPagination();
            //else
            //    return $this->getTrandingChallengesbyAvg21dayPagination();

            return  $this ->getTrandingChallengesPagination();
        } catch (\Throwable $th) {
                return $th->getMessage();
        }
    }





    public function getChallengeDayCount()
    {
        try
        {
            $challengStartDay=Carbon::createFromFormat('d-m-Y H:i:s', '1-8-2021 00:00:00');
            $today = Carbon::now()->addHour();
            $ChallengeDay = ceil($today->floatDiffInDays($challengStartDay));
            return $this->returnData('challenges Day', $ChallengeDay, "The response was successful");

        } catch (\Throwable $th) {
                return $th->getMessage();
        }

    }

    public function getTrandingInCapsules()
    {
        try {

            $challenges = DB::table('challenges')->where('in_leader_board', 1)->where('capsules','>=',20)->orderByDesc('priority')->orderByDesc('capsules')->orderBy('created_at')->get();
            foreach($challenges as $challenge)
            {
                $challenge->display_name = DB::table('users')->where('id',$challenge->user_id)->first()->display_name;
                $challenge->pictureName= DB::table('users')->where('id',$challenge->user_id)->first()->pictureName;
            }

            if (!$challenges) {
                return $this->returnError('', 'challenges not found');
            }
            return $this->returnData('challenges', $challenges,"The response was successful");
            } catch (\Throwable $th) {
                return $th->getMessage();
            }
    }


    public function getUserRankInCapsules()
    {
        try
        {
            $userId = Auth::id();
            $challenges = DB::table('challenges')->where('in_leader_board', 1)->orderByDesc('priority')->orderByDesc('capsules')->orderBy('created_at')->get();
            $position = $challenges->search(function ($cha) use ($userId) {
                return $cha->user_id == $userId;
            });

            if (!$challenges) {
                return $this->returnError('400', 'challenges not found');
            }

            return $this->returnData('rank', ($position+1) , "The response was successful");
        } catch (\Throwable $th) {
                return $th->getMessage();
        }
    }


    public function getCapsulesCountAndUserRankInCapsules()
    {
        $userId=Auth::id();
        try {
            $challenge= Challenge::select(['challenges.user_id','challenges.capsules'])
            ->where('user_id', $userId)->first();
            $rank = DB::table('challenges')->where('in_leader_board', 1)->orderByDesc('priority')->orderByDesc('capsules')->orderBy('created_at')->get();
            $position = $rank->search(function ($cha) use ($userId) {
                return $cha->user_id == $userId;
            });

            $challenge->rankInCapsules=$position+1;

            if (!empty($challenge)) {
                return $this->returnData('challenge', $challenge, "The response was successful");
            } else {
                return $this->returnError('', 'challenge is not found !!');
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }


    // public function tryEnterDayTask(Request $request)
    // {
    //     $inputs = $request->input();
    //     $mobileDate = $request->all();
    //     $validator = Validator::make($mobileDate,[
    //         'mobile_date' => 'required|string', //date must be in Berlin Time as string for example "2021-01-27 18:38:01"
    //     ]);

    //     if($validator->fails())
    //        return $this->returnValidationError($validator);
    //     try {
    //         $mob_date=new DateTime($inputs["mobile_date"]);
    //         $mob_dateDay=$mob_date->format('Y-m-d');
    //         $dateDay = new DateTime(now(), new DateTimeZone('Europe/Berlin'));
    //         $dateDayString=$dateDay->format('Y-m-d');
    //         if ($mob_dateDay==$dateDayString)
    //             return $this->returnData('CorrectTime', true,"your device time is correct");
    //         else
    //             return $this->returnError('400',"your device time is not correct");
    //     } catch (\Throwable $th) {
    //         return $this->returnError('', $th->getMessage());
    //     }
    // }

    // public function isUserFinishTody()
    // {
    //     try {
    //         $userId = Auth::id();
    //         $userChallenge =DB::table('Challenges')->where('user_id', $userId)->first();
    //         $datetime = explode(" ",$userChallenge->lastAddedDayDate);
    //         $userlastAddedDayDate = $datetime[0];
    //         $date = new DateTime(now(), new DateTimeZone('Europe/Berlin'));
    //         $datePart=$date->format('Y-m-d');


    //         if ($userlastAddedDayDate==null)
    //             return $this->returnData('NotFinishDay', true,"You can go to day task interface");
    //         else if ($userlastAddedDayDate < $datePart)
    //             return $this->returnData('NotFinishDay', true,"You can go to day task interface");
    //         else
    //            return $this->returnError('400',"You can not go to day task interface");
    //     }catch (\Throwable $th) {
    //         return $this->returnError('', $th->getMessage());
    //     }
    // }

    public function addDayPoints(Request $request)
    {
        $input = $request->input();
        $pointsCount = $request->all();
        $validator = Validator::make($pointsCount,[
            'points' => 'required|integer',
        ]);

        if($validator->fails())
           return $this->returnValidationError($validator);
        try {
            if ($input['points'] >10)
                return $this->returnError('400',"The max points must be equal or less than 10");
            else if ($input['points'] !=0)
            {
                $userId = Auth::id();
                $userChallenge =DB::table('challenges')->where('user_id', $userId)->first();
                $today =Carbon::now()->addHour();
                $dateonly=$today->toDateString();
                $todayTime01=Carbon::createFromFormat('Y-m-d H:i', $dateonly.' 01:00');
                $created_at= Carbon::createFromFormat('Y-m-d H:i:s' ,$userChallenge->created_at)->toDateString();
                $created_atDateTime0=Carbon::createFromFormat('Y-m-d H:i', $created_at.' 00:00');
                $diff_in_days = ceil($todayTime01->floatDiffInDays($created_atDateTime0));
                if($userChallenge->lastAddedDayDate!=null)
                {
                    $userlastAddedDate= Carbon::createFromFormat('Y-m-d H:i:s' ,$userChallenge->lastAddedDayDate)->toDateString();
                   $userlastAddedDateTime0=Carbon::createFromFormat('Y-m-d H:i', $userlastAddedDate.' 00:00');
                }
                if ($userChallenge->lastAddedDayDate==null)
                {
                    $result =DB::table('challenges')->where('user_id', $userId)->update([
                        'points' => ($userChallenge->points + $input['points']),
                        'lastAddedDayDate'=>$today,
                        'challengeDaysCount'=>$diff_in_days,
                        'average'=>($userChallenge->points + $input['points'])/$diff_in_days,
                        ]);
                    return $this->returnData('update', $result,"Points is added Successfully!");
                }
                else if ($userlastAddedDateTime0->toDateString() < $todayTime01->toDateString())
                {
                    $result =DB::table('challenges')->where('user_id', $userId)->update([
                        'points' => ($userChallenge->points + $input['points']),
                        'lastAddedDayDate'=>$today,
                        'challengeDaysCount'=>$diff_in_days,
                        'average'=>($userChallenge->points + $input['points'])/$diff_in_days,
                        ]);
                    return $this->returnData('update', $result,"Points is added Successfully!");
                }
                else
                    return $this->returnError('400',"You add your points in this day previously ");
            }
            else
               return $this->returnData('update', false,"Points of this tody equals zero");
        }  catch (\Throwable $th) {
            return $this->returnError('', $th->getMessage());
        }
    }

    public function addDayCapsules(Request $request)
    {
        $input = $request->input();
        $cupsuleCount = $request->all();
        $validator = Validator::make($cupsuleCount,[
            'capsules' => 'required|integer',
        ]);

        if($validator->fails())
           return $this->returnValidationError($validator);
        try {
              $userId = Auth::id();
              $userChallenge =DB::table('challenges')->where('user_id', $userId)->first();
              $datetime = explode(" ",$userChallenge->lastAddedCapsulesDate);
              $today = Carbon::now()->addHour();
              $addday=0;
              if ($userChallenge->lastAddedCapsulesDate==null)
              {
                $addday=1;
                $datetime = explode(" ",$userChallenge->created_at);
              }
              $userlastAddedCapsulesDate = $datetime[0];

              $diff_in_days =$today->DiffInDays($userlastAddedCapsulesDate)+$addday;

              $validCapslesNum=$diff_in_days*20;

              if ($input['capsules'] !=0)
              {
                $hint="";
                if ($input['capsules'] > $validCapslesNum)
                {
                  $input['capsules']=$validCapslesNum;
                  $hint="We will add ".$validCapslesNum." capsules only";
                }
                if($diff_in_days>0)
                {
                    $result =DB::table('challenges')->where('user_id', $userId)->update([
                        'capsules' => ($userChallenge->capsules + $input['capsules']),
                        'lastAddedCapsulesDate'=>$today,
                        ]);
                    return $this->returnData('update', $result,"Capsules is added Successfully!".$hint);
                }
                else
                    return $this->returnError('400',"You add your capsules in this day previously");
             }
             else
               return $this->returnData('update', false,"Capsules of this tody equals zero");
        }  catch (\Throwable $th) {
            return $this->returnError('', $th->getMessage());
        }
    }


     public function getCountHoursAfterAddCapsules()
     {
         try
         {
            $userId = Auth::id();
                $userChallenge =DB::table('challenges')->where('user_id', $userId)->first();
                $today =Carbon::now()->addHour();
                $created_at= Carbon::createFromFormat('Y-m-d H:i:s' ,$userChallenge->lastAddedCapsulesDate);
                $diff_in_hours = $today->floatDiffInRealHours($created_at);

                return $this->returnData('differnet_In_hours', $diff_in_hours , "The response was successful");
             } catch (\Throwable $th) {
            return $th->getMessage();
             }
     }



    public function challengVerified(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer',
            'is_challengVerified' => 'string|in:true,false',
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
                    return $this->returnError('400', 'You do not have privileges to verified challenge!');

                $user = DB::table('users')->where('id', $inputs['user_id'])->get();
                if (count($user) == 0)
                    return $this->returnError('400', 'user is not found !!');

                $result = DB::table('challenges')->where('user_id' , $inputs['user_id'])->update([
                    'is_challengVerified' => $inputs['is_challengVerified'],
                    ]);
                return $this->returnData('update', $result,"Challenge is verified Successfully!");
            } catch (\Throwable $th) {
                return $this->returnError("", $th->getMessage());
            }
        }
    }
}
