<?php

namespace App\Http\Controllers;

use App\Reminder;
use App\ReminderConfirmation;
use App\User;
use Illuminate\Http\Request;

class ReminderController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Reminder  $reminder
     * @return \Illuminate\Http\Response
     */
    public function show(Reminder $reminder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Reminder  $reminder
     * @return \Illuminate\Http\Response
     */
    public function edit(Reminder $reminder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Reminder  $reminder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reminder $reminder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Reminder  $reminder
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reminder $reminder)
    {
        //
    }
	
	public function createReminder($title, $time, $frequency, $name, $user, $apiURL, $apiToken) {
		$reminder = new Reminder();
		$reminder->title = $title;
		$reminder->time = $time;
		$reminder->frequency = $frequency;
		$reminder->name = $name;
		
		$reminder->user()->associate($user);
		
		//POST /v1/alerts/reminders
		$data = $this->generateSetReminderJson($time, $frequency, $name, $title);
		
		$headers = [
			"Content-length: " . strlen($data),
			"Authorization: Bearer " . $apiToken,
			"Content-Type: application/json"
		];
		$response = json_decode($this->sendPost($apiURL, "/v1/alerts/reminders", $headers, $data));
		file_put_contents('/var/www/html/reminders/app/debug.txt', json_encode($response) . "\r\n\r\n", FILE_APPEND);
		if(isset($response->status) && $response->status == "ON") {
			$reminder->amazon_id = $response->alertToken;
			$reminder->save();
					file_put_contents('/var/www/html/reminders/app/debug.txt', json_encode($reminder) . "\r\n\r\n", FILE_APPEND);
			return true;
		} else {
			return false;
		}
	}
	
	public function reminderStatusChanged($payload, $bot) {
		file_put_contents('/var/www/html/reminders/app/debug.txt', json_encode($bot->getMessage()->payload) . "\r\n\r\n", FILE_APPEND);
	}

	/**
	 * @param $base
	 * @param $endpoint
	 * @param $headers
	 * @param $data
	 *
	 * @return bool|string
	 */
	public function sendPost($base, $endpoint, $headers, $data) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$base . $endpoint);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);  //Post Fields
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return $server_output;
	}

	/**
	 * @param $base
	 * @param $endpoint
	 * @param $headers
	 * @param $data
	 *
	 * @return bool|string
	 */
	public function sendPut($base, $endpoint, $headers, $data) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$base . $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return $server_output;
	}


	/**
	 * @param $base
	 * @param $endpoint
	 * @param $headers
	 * @param $data
	 *
	 * @return bool|string
	 */
	public function sendDelete($base, $endpoint, $headers) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$base . $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return $server_output;
	}

	/**
	 * @param $base
	 * @param $endpoint
	 * @param $headers
	 * @param $data
	 *
	 * @return bool|string
	 */
	public function sendGet($base, $endpoint, $headers) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$base . $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$server_output = curl_exec ($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close ($ch);
		return $server_output;
	}
	
	public function generateSetReminderJson($reminderTime, $frequency, $name, $title) {
		$time = strtotime($reminderTime);
		if($time <= time()) {
			$time += 86400;
		}
		$freqs = ['daily' => "DAILY", "weekly" => "WEEKLY", "monthly" => "MONTHLY"];
		$data = '{
		   "requestTime" : "'.date('c').'",
		   "trigger": {
				"type" : "SCHEDULED_ABSOLUTE",
				"scheduledTime" : "'.date('c', $time).'",
				"timeZoneId" : "Europe/London",
				"recurrence" : {
				  "recurrenceRules" : [
					 "FREQ='.$freqs[$frequency].';BYHOUR='.date('H', $time).';BYMINUTE='.date('i', $time).';BYSECOND=0;INTERVAL=1;"
				  ]               
				}
		   },
		   "alertInfo": {
				"spokenInfo": {
					"content": [{
						"locale": "en-GB", 
						"text": "'.$name.', Take '.$title.'",
						"ssml": "<speak>This is a reminder for '.$name.' to take '.$title.'. Don\'t forget to open the pill reminder skill to confirm once you have taken them</speak>"
					}]
				}
			},
			"pushNotification" : {                            
				 "status" : "ENABLED"
			}
		}';
		
		return $data;
	}

	public function getReminders( $message, $payload ) {
		$apiToken = $message['context']['System']['user']['permissions']['consentToken'];
		$endpoint = "/v1/alerts/reminders";
		$header = ["Content-Type: application/json", "Authorization: Bearer " . $apiToken];
		file_put_contents($payload['message']['filename'], $this->sendGet("https://api.eu.amazonalexa.com", $endpoint, $header));
	}

	public function createReminderFromAmazon($alert, $user) {
		$time = date('H:i', strtotime($alert->trigger->scheduledTime));
		$frequency  = [];
		preg_match('/FREQ=(.*?);/', $alert->trigger->recurrence->recurrenceRules[0], $frequency);
		$alertText = $alert->alertInfo->spokenInfo->content[0]->ssml;
		$alertInfo = [];
		preg_match('/This is a reminder for (.*?) to take (.*?)<\/speak>/', $alertText, $alertInfo);

		$reminder            = new Reminder();
		$reminder->title     = $alertInfo[2];
		$reminder->time      = $time;
		$reminder->frequency = $frequency[1];
		$reminder->name      = $alertInfo[1];
		$reminder->amazon_id = $alert->alertToken;

		$reminder->user()->associate( $user );
		$reminder->save();
		return $reminder;
	}



	public function getUserAPIReminders($userID) {
		//create random file
		$filename = storage_path(uniqid() . ".json");
		touch($filename);

		//send request for skill message
		$data = '{"data":{ "filename": "'.$filename.'", "method": "getReminders"}, "expiresAfterSeconds": 60}';
		$this->sendSkillMessageRequest($userID, $data);

		//poll created file until skill message updates it
		while(file_get_contents($filename) == "") {
			sleep(.5);
		}
		$data = json_decode(file_get_contents($filename));
		unlink($filename);

		$reminders = [];
		if($data->totalCount > 0) {
			foreach ( $data->alerts as $alert ) {
				$id = $alert->alertToken;
				$reminder = Reminder::query()->where('amazon_id', $id);
				if($reminder->count() > 0) {
					$reminders[] = $reminder->get()->first();
				} else {
					$user = User::where('alexa_id', $userID)->get()->first();
					$reminders[] = $this->createReminderFromAmazon($alert, $user);
				}
			}
		}
		return $reminders;
	}

	public function sendSkillMessageRequest( $userID, $data ) {
		//https://api.eu.amazonalexa.com/v1/skillmessages/users/amzn1.ask.account.AGYYKCN2O52JIXKH3G2H2R4JHV2XB5ZDVJ7N2F3N6IDQMMP3FH7P5GAN4UOZDJGSM4UQ6BRRKLU5SIHMLZ6E3BXZB4OMKG7KLXSD3JRPBZLK6HOZJRUPUCNINZNZ3MIYR44BBSC7ARFHV37T3DFIB5VV45XNRBOKR5GWJLYH472KRLRI3C47DLLTISAZNQOOFBSDWBC2V4E6FGY
		$base = "https://api.eu.amazonalexa.com";
		$endpoint = "/v1/skillmessages/users/" . $userID;

		$this->sendPost($base, $endpoint, ["Authorization: Bearer " . $this->getAccessToken(), "Content-Type: application/json"], $data);
	}

	public function getAccessToken() {
		$headers = ["Content-Type: application/x-www-form-urlencoded;charset=UTF-8"];

		$data = "grant_type=client_credentials&client_id=" . env('alexa_client_id') . "&client_secret=" . env('alexa_client_secret') . "&scope=alexa:skill_messaging";

		$reponse = json_decode($this->sendPost("https://api.amazon.com", "/auth/o2/token", $headers, $data));

		return $reponse->access_token;
	}

	public function getUserApiToken($message, $payload) {
		$apiToken = $message['context']['System']['user']['permissions']['consentToken'];
		file_put_contents($payload['message']['filename'], $apiToken);
	}

	public function updateReminder( $payload, $bot ) {
		echo "<pre>";
		var_dump($payload);
		var_dump($bot->getMessage()->payload);
		echo "</pre>";
	}
	
	public function createReminderConfirmation($payload, $bot) {
			$reminder = Reminder::query()->where('amazon_id', $payload['body']['alertToken'])->get()->first();
			$confirmation = new ReminderConfirmation();
			$confirmation->reminder_id = $reminder->id;
			$confirmation->user_id = $reminder->user_id;
			$confirmation->reminder_time = $carbon = \Carbon\Carbon::createFromTimestamp(strtotime($payload['timestamp']));
			
			$confirmation->save();
	}

	public function updateAPIReminder( Reminder $reminder ) {
		//create random file
		$filename = storage_path(uniqid() . ".json");
		touch($filename);

		//send request for skill message
		$data = '{"data":{ "filename": "'.$filename.'", "method": "getUserApiToken"}, "expiresAfterSeconds": 60}';
		$this->sendSkillMessageRequest($reminder->getUser()->alexa_id, $data);

		//poll created file until skill message updates it
		while(file_get_contents($filename) == "") {
			sleep(.5);
		}
		$token = file_get_contents($filename);
		unlink($filename);

		//PUT /v1/alerts/reminders/{id}

		$endpoint = "/v1/alerts/reminders/{$reminder->amazon_id}";
		$header = ["Content-Type: application/json", "Authorization: Bearer " . $token];
		$body = $this->generateUpdateReminderBody($reminder);
		$response = $this->sendPut("https://api.eu.amazonalexa.com", $endpoint, $header, $body);
	}

	public function deleteAPIReminder( Reminder $reminder ) {
		//create random file
		$filename = storage_path(uniqid() . ".json");
		touch($filename);

		//send request for skill message
		$data = '{"data":{ "filename": "'.$filename.'", "method": "getUserApiToken"}, "expiresAfterSeconds": 60}';
		$this->sendSkillMessageRequest($reminder->getUser()->alexa_id, $data);

		//poll created file until skill message updates it
		while(file_get_contents($filename) == "") {
			sleep(.5);
		}
		$token = file_get_contents($filename);
		unlink($filename);

		$endpoint = "/v1/alerts/reminders/{$reminder->amazon_id}";
		$header = ["Authorization: Bearer " . $token];
		$response = $this->sendDelete("https://api.eu.amazonalexa.com", $endpoint, $header);
	}

	public function generateUpdateReminderBody( Reminder $reminder ) {
		$time = strtotime( $reminder->time );
		if ( $time <= time() ) {
			$time += 86400;
		}
		$freqs = [ 'daily' => "DAILY", "weekly" => "WEEKLY", "monthly" => "MONTHLY" ];
		$days = [ "", "MO", "TU", "WE", "TH", "FR", "SA", "SU" ];
		$data  = '{
		   "requestTime" : "' . date( 'c' ) . '",
		   "trigger": {
				"type" : "SCHEDULED_ABSOLUTE",
				"scheduledTime" : "' . date( 'c', $time ) . '",
				"timeZoneId" : "Europe/London",
				"recurrence" : {
				  "recurrenceRules" : [
					 "FREQ=' . ( ($freqs[ $reminder->frequency ] ?? false) ? $freqs[ $reminder->frequency ] : $reminder->frequency) . ';' . ($reminder->frequency != "DAILY" ? "BYDAY=" . $days[date('N', $time)] . ";" : "" ) . 'BYHOUR=' . date( 'H', $time ) . ';BYMINUTE=' . date( 'i', $time ) . ';BYSECOND=0;INTERVAL=1;"
				  ]               
				}
		   },
		   "alertInfo": {
				"spokenInfo": {
					"content": [{
						"locale": "en-GB", 
						"text": "' . $reminder->name . ', Take ' . $reminder->title . '",
						"ssml": "<speak>This is a reminder for ' . $reminder->name . ' to take ' . $reminder->title . '</speak>"
					}]
				}
			},
			"pushNotification" : {                            
				 "status" : "ENABLED"
			}
		}';
		return $data;
	}

}