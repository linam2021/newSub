<?php

namespace App\Traits;

trait Messenger
{
    //$data is the data requested by the client (database data)
    //message is the message choosen by the programmer
    //after handling the request
    public function sendResponse($data, $message)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data'=> $data
        ];

        return  response()->json($response, 200);
    }

    //$errorData is the error data returned by the validator
    //message is the message choosen by the programmer
    //after handling the request
    public function sendError($errorData = [], $message ='Unsuccessful', $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errorData)) {
            $response['errors'] = $errorData;
        }

        return response()->json($response, $code);
    }
}
