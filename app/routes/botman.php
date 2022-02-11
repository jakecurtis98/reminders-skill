<?php
use App\Http\Controllers\BotManController;
	use App\Http\Controllers\AppController;
	use App\Http\Controllers\ReminderController;
	use BotMan\BotMan\BotMan;
	use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

	/** @var \BotMan\BotMan\BotMan $botman */
	$botman = resolve('botman');

$botman->hears('HelloWorldIntent', function($bot) {
	$message = OutgoingMessage::create('This is the spoken response');
    $bot->reply($message);
});



/* Launch Skill */
$botman->on('LaunchRequest', AppController::class . "@launch");

$botman->on('Messaging.MessageReceived', AppController::class . "@message");

$botman->on('Reminders.ReminderUpdated', ReminderController::class . "@updateReminder");
$botman->on('Reminders.ReminderStatusChanged', ReminderController::class . "@reminderStatusChanged");
$botman->on('Reminders.ReminderStarted', ReminderController::class . "@createReminderConfirmation");
//$botman->on('LaunchRequest', AppController::class . "@sendPermRequest");

/* User says yes */
$botman->hears('reminderTime', function($bot) {
	/** @var BotMan $bot */
	$attr = $bot->getMessage()->payload['session']['attributes'] ?? [];
	if(!isset($attr['expectNext']) || $attr['expectNext'] == "reminderTime") {
		resolve('App\Http\Controllers\AppController')->reminderTime($bot);
	}
});

$botman->hears('createReminder', function($bot) {
	return resolve('App\Http\Controllers\AppController')->startWrite($bot);
});

$botman->hears('showExisting', function($bot) {
	resolve('App\Http\Controllers\AppController')->showExisting($bot);
});


/* User says yes */
$botman->hears('AMAZON.YesIntent', function($bot) {
	/** @var BotMan $bot */
	$attr = $bot->getMessage()->payload['session']['attributes'] ?? [];
	if(isset($attr['question_yes_response'])) {
		resolve('App\Http\Controllers\AppController')->{$attr['question_yes_response']}($bot);
	}
});

$botman->fallback(function($bot) {
	$message = $bot->getMessage()->payload;
	$attr = $message['session']['attributes'] ?? [];
	if(isset($attr['expectNext'])) {
		resolve('App\Http\Controllers\AppController')->{$attr['expectNext']}($bot);
	}
});