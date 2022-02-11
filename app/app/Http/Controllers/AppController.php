<?php

namespace App\Http\Controllers;

use App\User;
use App\Reminder;
use App\ReminderConfirmation;
use Carbon\Carbon;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Illuminate\Http\Request;

class AppController extends Controller
{

	public $appName = "Pill Reminder";

	/**
	 * @param $payload
	 * @param $bot BotMan
	 */
	public function launch($payload, $bot) {
		$user = $this->userIsLinked($bot->getMessage()->getPayload());
		if($user) {
			if($user->alexa_id == null) {
				$user->alexa_id = $bot->getMessage()->getPayload()['session']['user']['userId'];
				$user->save();
			}			
			if($user->hasReminders()) {
				if($user->hasUnconfirmedReminders()) {
					$confirmation = $user->confirmations()->where('confirmed', 0)->whereDate('reminder_time', '>=', Carbon::now()->subDays(2))->get()->last();
					$reminder = $confirmation->getReminder();
					//at " . $reminder->time . " every " . $freq[$reminder->frequency] . " " . $reminder->name . " will be reminded to take " . $reminder->title . " starting from " . date("l jS F Y", strtotime($reminder->created_at)) . ". ";
					$time = strtotime($confirmation->reminder_time);
					$message = OutgoingMessage::create( "Hello, has {$reminder->name} taken {$reminder->title} from ".date('h:i a', $time) . " on " . date('l jS F', $time)."?");
					return $bot->reply( $message, [ "shouldEndSession" => false, "session" => ["question_yes_response" => "confirmReminder", "question_no_response" => "unconfirmReminder", "confirmation_id" => $confirmation->id]] );
				}
				$message = OutgoingMessage::create( "Welcome to {$this->appName}, would you like to create a new reminder or hear your existing reminders?");
				return $bot->reply( $message, [ "shouldEndSession" => false] );
			} else {
				$message = OutgoingMessage::create( "Thanks for linking your account. Now it’s time to set up your first reminder. I will ask for the time you would like the reminder, how often you need it to recur, and a name that will help you to know which medicine the reminder is for. Are you ready to start?");
				return $bot->reply( $message, [ "shouldEndSession" => false, "session" => ["question_yes_response" => "startWrite", "question_no_response" => "sessionEnd"]] );
			}
		} else {
			$message = OutgoingMessage::create( "Welcome to {$this->appName}, please link your alexa account to our system to continue. I have sent a link to your alexa app for you to do this. Once you’ve linked your account, just open this skill again to set up some reminders" );
			return $bot->reply( $message, [ "shouldEndSession" => true ] );
		}
	}

	public function createReminder($bot) {
		$message = OutgoingMessage::create( "Ok, first of all, what time would you like to be reminded?");
		return $bot->reply( $message, [ "shouldEndSession" => false, "session" => ["expectNext" => "reminderTime"]] );
	}
	
	public function showExisting($bot) {
		$user = $this->userIsLinked($bot->getMessage()->getPayload());
		if($user) {
			if($user->hasReminders()) {
				$reminders = $user->reminders()->get();
				$messageStr ="you have " . $reminders->count() . " reminders set. ";
				foreach($reminders as $reminder) {
					$freq = ["weekly" => "week", "monthly" => "month", "daily" => "day"];
					$messageStr .= "at " . $reminder->time . " every " . $freq[$reminder->frequency] . " " . $reminder->name . " will be reminded to take " . $reminder->title . " starting from " . date("l jS F Y", strtotime($reminder->created_at)) . ". ";
				}
				
				$message = OutgoingMessage::create($messageStr);
				return $bot->reply( $message, [ "shouldEndSession" => true ] );
			} else {
				$message = OutgoingMessage::create( "You haven't set any remminders yet. To create a new one, just ask me to create a reminder");
				return $bot->reply( $message, [ "shouldEndSession" => true] );
			}
		} else {
			$message = OutgoingMessage::create( "Welcome to {$this->appName}, please link your alexa account to our system to continue. I have sent a link to your alexa app for you to do this. Once you’ve linked your account, just open this skill again to set up some reminders" );
			return $bot->reply( $message, [ "shouldEndSession" => true ] );
		}
	}

	/**
	 * User confirms they're ready to create reminder
	 */
	public function startWrite($bot) {
		
		
		/*$name = "jake";
		$title = "pain killers";
		$frequency = "daily";
		$time = "21:18";
		$user = $this->userIsLinked($bot->getMessage()->getPayload());
		
		$apiURL = $bot->getMessage()->payload['context']['System']['apiEndpoint'];
		$apiToken = $bot->getMessage()->payload['context']['System']['apiAccessToken'];
		
		$reminderController = resolve('App\Http\Controllers\ReminderController');
		$created = $reminderController->createReminder($title, $time, $frequency, $name, $user, $apiURL, $apiToken);
		file_put_contents('/var/www/html/reminders/app/debug.txt', $created . "\r\n\r\n", FILE_APPEND);*/

		//return $this->sendPermRequest($bot);exit;
		$message = OutgoingMessage::create( "Ok, first of all, what time would you like to be reminded?");
		return $bot->reply( $message, [ "shouldEndSession" => false, "session" => ["expectNext" => "reminderTime"]] );
	}

	/**
	 * user sets time
	 */
	public function reminderTime($bot) {
		$slots = $bot->getMessage()->getExtras('slots');
		$time = $slots['time']['value'];
		$message = OutgoingMessage::create( "Thanks. Next, how often would you like this reminder to recur? Daily, weekly or monthly?");
		return $bot->reply( $message, [ " shouldEndSession" => false, "session" => ["expectNext" => "reminderFrequency", "time" => $time]] );
	}
	
	public function reminderFrequency($bot) {
		$slots = $bot->getMessage()->getExtras('slots');
		$sessionAttrs = $this->getSessionAtrrs($bot);
		$frequency = $slots['frequency']['value'];
		$time = $sessionAttrs['time'];
		
		$message = OutgoingMessage::create( "Thank you, almost done now. What is this reminder for? This should be something that will help you remember the medication you need to take, for example The blue pills, the painkillers, or just simply tablets");
		return $bot->reply( $message, [ "shouldEndSession" => false, "session" => ["expectNext" => "reminderTitle", "time" => $time, "frequency" => $frequency]]);		
	}
	
	public function reminderTitle($bot) {
		$slots = $bot->getMessage()->getExtras('slots');
		$sessionAttrs = $this->getSessionAtrrs($bot);
		
		$title = $slots['title']['value'];
		$frequency = $sessionAttrs['frequency'];
		$time = $sessionAttrs['time'];
		
		$message = OutgoingMessage::create( "great. Finally, who is this reminder for?");
		return $bot->reply( $message, [ "shouldEndSession" => false, "session" => ["expectNext" => "reminderName", "time" => $time, "frequency" => $frequency, "title" => $title]]);		
	}
	
	public function reminderName($bot) {
		$slots = $bot->getMessage()->getExtras('slots');
		$sessionAttrs = $this->getSessionAtrrs($bot);
		
		$name = $slots['name']['value'];
		$title = $sessionAttrs['title'];
		$frequency = $sessionAttrs['frequency'];
		$time = $sessionAttrs['time'];
		$user = $this->userIsLinked($bot->getMessage()->getPayload());
		
		$apiURL = $bot->getMessage()->payload['context']['System']['apiEndpoint'];
		$apiToken = $bot->getMessage()->payload['context']['System']['apiAccessToken'];
		
		//$reminderController = resolve('App\Http\Controllers\ReminderController');
		//$reminderController->createReminder($title, $time, $frequency, $name, $user, $apiURL, $apiToken);
		
		
		
		$message = OutgoingMessage::create( "Ok. to confirm, you would like me to remind" . $name . " " . $frequency . " at " . $time . " to take " . $title . "?" );
		return $bot->reply( $message, [ "shouldEndSession" => false, "session" => ["question_yes_response" => "sendReminderRequest", "question_no_response" => "sessionEnd", "time" => $time, "frequency" => $frequency, "title" => $title, "name" => $name]]);
	}

	public function message($payload, $bot) {
		$reminderController = resolve('App\Http\Controllers\ReminderController');
		$method = $payload['message']['method'];
		$reminders = $reminderController->{$method}($bot->getMessage()->payload, $payload);
	}

	public function sendReminderRequest($bot) {
		$sessionAttrs = $this->getSessionAtrrs($bot);
		$name = $sessionAttrs['name'];
		$title = $sessionAttrs['title'];
		$frequency = $sessionAttrs['frequency'];
		$time = $sessionAttrs['time'];
		$user = $this->userIsLinked($bot->getMessage()->getPayload());
		
		$apiURL = $bot->getMessage()->payload['context']['System']['apiEndpoint'];
		$apiToken = $bot->getMessage()->payload['context']['System']['apiAccessToken'];
		
		$reminderController = resolve('App\Http\Controllers\ReminderController');
		$created = $reminderController->createReminder($title, $time, $frequency, $name, $user, $apiURL, $apiToken);
		
		if($created) {
			$message = OutgoingMessage::create( "Thanks, i have created that for you. Once i've reminded you to take your " . $title . ", you'll need to open this skill again to confirm you have taken them.");
			return $bot->reply( $message, [ "shouldEndSession" => true]);
		} else {
			$message = OutgoingMessage::create( "Sorry, there was a problem creating your reminder. Please try again later" );
			return $bot->reply( $message, [ "shouldEndSession" => true]);
		}

	}
	
	public function confirmReminder($bot) {
		$session = $this->getSessionAtrrs($bot);
		$confirmation = ReminderConfirmation::find($session['confirmation_id']);
		$confirmation->confirmed = 1;
		$confirmation->confirmed_time = Carbon::now();
		$confirmation->save();
		
		$message = OutgoingMessage::create( "Thanks for letting me know" );
		return $bot->reply( $message, [ "shouldEndSession" => true]);
	}

	/**
	 * @param $message
	 *
	 * @return false|User
	 */
	public function userIsLinked($message) {
		$accessToken = $message['session']['user']['accessToken'] ?? false;
		if($accessToken) {
			$user = User::query()->where('alexa_token', $accessToken);
			if($user->count() > 0) {
				return $user->get()->first();
			}
		}
		return false;
	}
	
	public function getSessionAtrrs($bot) {
		return $bot->getMessage()->payload['session']['attributes'] ?? [];
	}

}
