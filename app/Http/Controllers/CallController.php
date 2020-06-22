<?php

namespace App\Http\Controllers;

use App\Call;
use Illuminate\Http\Request;
use AfricasTalking\SDK\AfricasTalking;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CallController extends Controller
{
    public function initiateCall(Request $request)
    {
        Log::info($request);
        $phonenumber = $request->agentnumber;
        $othernumber = $request->othernumber;
        $requestId = $request->requestId;
        // Set your app credentials
        $username = "lexeme";
        $apiKey   = "18a0ab0cd1e6a6d16cd38ed464b7839a2ffef87431077a60c1b573ef644d0ca5";

        $client = new Client();


        // Initialize the SDK
        // $AT       = new AfricasTalking($username, $apiKey);

        // // Get the voice service
        // $voice    = $AT->voice();

        // Set your Africa's Talking phone number in international format
        $from     = "+254711082033";

        // Set the numbers you want to call to in a comma-separated list
        $to       =  $phonenumber;

        try {
            // Make the call
            $results = $client->request('POST', 'https://voice.africastalking.com/call', [
                'form_params' => [
                    'username' => $username,
                    'from' => $from,
                    'to' => $to,
                    'clientRequestId' => $requestId
                    // 'callLeg' =>
                ],
                'headers' => [
                    'User-Agent' => 'testing/1.0',
                    'Accept'     => 'application/json',
                    'username' => 'lexeme',
                    'apiKey' => $apiKey

                    // 'callLeg' => 'callee',
                    // 'holdMusicUrl' => 'http://206.189.235.13/hold.mp3'

                ]
            ]);

            $stringBody = (string) $results->getBody();
            $call = new Call;

            Log::info(gettype($stringBody));

            $stringBody = json_decode($stringBody);
            $stringBody = json_encode($stringBody);
            Log::info($stringBody);
            $stringBody = json_decode($stringBody);
            $call->session_id = $stringBody->entries[0]->sessionId;
            $call->callerNumber = $phonenumber;
            $call->CalledNumber = $othernumber;
            $call->clientRequest = $requestId;

            if ($call->save()) {
                $response = array("status" => 'success', $stringBody);
                return json_encode($response);
            }

            // print_r($results);
        } catch (Exception $e) {

            return json_encode(array("status" => 'failed', "error" => $e->getMessage()));
            // echo "Error: " . $e->getMessage();
        }
    }

    public function callback(Request $request)
    {
        // Log::info($request);
        $isActive  = $request->isActive;
        if ($isActive == 1) {

            if (isset($_POST['dtmfDigits'])) {
                # code...
                $confirmation = $_POST['dtmfDigits'];

                if ($confirmation  == 1) {
                    $call = Call::where('session_id', $request->sessionId)->first();
                    $othernumber = $call->CalledNumber;

                    $response  = '<?xml version="1.0" encoding="UTF-8"?>';
                    $response .= '<Response>';
                    $response .= '<Dial record="true" sequential="true" phoneNumbers="' . $othernumber . '"  />';
                    $response .= '</Response>';

                    // Print the response onto the page so that our gateway can read it
                    header('Content-type: application/xml');
                    echo $response;
                }
            } else {
                $response  = '<?xml version="1.0" encoding="UTF-8"?>';
                $response .= '<Response>';
                $response .= '<GetDigits finishOnKey="#" >';
                $response .= '<Say>Press 1 followed by the hash sign to continue with the audit</Say>';
                $response .= '</GetDigits>';
                $response .= '</Response>';

                // Print the response onto the page so that our gateway can read it
                header('Content-type: application/xml');
                echo $response;
            }

            // DB::table('calls')->where('session_id',  $request->sessionId)->update(['status' => 'Active']);



        } else {
            DB::table('calls')->where('session_id',  $$request->sessionId)->update(['status' => 'Completed', 'recordinUrl' =>  $_POST['recordingUrl'], 'call_duration' => $_POST['dialDurationInSeconds'], 'duration' => $_POST['durationInSeconds'], 'amount' => $_POST['amount']]);
        }
    }

    public function events(Request $request)
    {
        Log::warning($request);
    }
}
