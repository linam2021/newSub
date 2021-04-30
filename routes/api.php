<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
  User API
*/
Route::namespace('App\Http\Controllers\api')->group(function(){
    Route::middleware('api' , 'checkPassword')->group(function(){
        Route::post('register' , 'UserController@register');
        Route::post('verifyEmail','UserController@verifyEmail');
        Route::post('resendVerifyCode','UserController@resendVerifyCode');
        Route::post('login' , 'UserController@login');
        Route::post('logout' , 'UserController@logout');

    });

    Route::middleware('auth:api' , 'checkPassword')->group(function(){
        Route::post('updateUser' , 'UserController@updateUser');
        Route::post('addPicture' , 'UserController@addPicture');
        Route::post('updateName' , 'UserController@updateName');
        Route::post('getUserPictureName' , 'UserController@getUserPictureName');
        Route::post('deleteUser' , 'UserController@deleteUser');
        Route::post('searchForUser' , 'UserController@searchForUser');
    });
});

/*
  Challenge API
*/ 

Route::middleware('auth:api' , 'checkPassword')->namespace('App\Http\Controllers\api')->group(function(){
    Route::post('createChallenge' , 'ChallengeController@createChallenge');
    Route::post('deleteChallenge' , 'ChallengeController@deleteChallenge');
    Route::post('getChallenge' , 'ChallengeController@getChallenge');
    //Route::get('getTrandingChallenges' , 'ChallengeController@getTrandingChallenges');
    //Route::get('getTrandingChallengesbyAvg21day' , 'ChallengeController@getTrandingChallengesbyAvg21day');
    Route::post('getTrandingInPoints' , 'ChallengeController@getTrandingInPoints');
    Route::post('getTrandingInPointsPagination' , 'ChallengeController@getTrandingInPointsPagination');
    Route::post('getChallengeDayCount' , 'ChallengeController@getChallengeDayCount');
    Route::post('getTrandingInCapsules' , 'ChallengeController@getTrandingInCapsules');
    Route::post('getUserRankInCapsules' , 'ChallengeController@getUserRankInCapsules');
    Route::post('getCapsulesCountAndUserRankInCapsules' , 'ChallengeController@getCapsulesCountAndUserRankInCapsules');
    //Route::post('tryEnterDayTask' , 'ChallengeController@tryEnterDayTask');
    //Route::post('isUserFinishTody' , 'ChallengeController@isUserFinishTody');
    Route::post('addDayPoints' , 'ChallengeController@addDayPoints');
    Route::post('addDayCapsules' , 'ChallengeController@addDayCapsules');
    Route::post('challengVerified' , 'ChallengeController@challengVerified');
});
