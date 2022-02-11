<?php

	use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
	use MaxBeckers\AmazonAlexa\Helper\SsmlGenerator;
	use MaxBeckers\AmazonAlexa\Request\Request;
	use MaxBeckers\AmazonAlexa\Response\Card;
	use MaxBeckers\AmazonAlexa\Validation\RequestValidator;
	require 'db.php';
	class alexa {

		public $alexaRequest;
		public $db;
		public $session;
		public $userID;
        public $slots;
        public $requestID;
		public function __construct($requestBody) {
			$this->db = new db('localhost', 'root', 'bethany02', 'notes');
			$this->alexaRequest = Request::fromAmazonRequest($requestBody, $_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE']);
			$this->session = $this->alexaRequest->session->attributes;
			$this->userID = $this->alexaRequest->session->user->userId;
		}

		public function validate() {
			// Request validation
			$validator = new RequestValidator();
			$validator->validate($this->alexaRequest);
		}

		public function handle() {
			$this->validate();
			$this->beforeLaunch();
			$intentType = $this->alexaRequest->request->type;
			if($intentType == "IntentRequest") {
				$intent      = str_replace( "AMAZON.", "", $this->alexaRequest->request->intent->name );
				$this->slots = [];
				foreach ( $this->alexaRequest->request->intent->slots as $slot ) {
					$this->slots[ $slot->name ] = $slot->value;
				}
				//file_put_contents('testing.txt', json_encode($this->slots), FILE_APPEND);
				if ( method_exists( $this, "handleIntent_" . $intent ) ) {
					call_user_func_array( [ $this, "handleIntent_" . $intent ], [] );
				}
			} elseif($intentType == "Connections.Response") {
				$this->handleConnectionResponse($this->alexaRequest->request->payload->purchaseResult);
			} else {
				call_user_func_array([$this, "handle_" . $intentType], []);
			}
		}

		public function handleConnectionResponse($purchaseResult) {
			if($purchaseResult == "Error") {
				print "{\"response\": {\"outputSpeech\": {\"ssml\": \"<speak>Would you like to write another new note or read an existing one?</speak>\",\"type\": \"SSML\"},\"shouldEndSession\": false},\"sessionAttributes\": {\"started\": \"true\",\"state\": \"started\"},\"version\": \"1.0\"}";
				exit;
			} elseif($purchaseResult == "DECLINED") {
				print "{\"response\": {\"outputSpeech\": {\"ssml\": \"<speak>Would you like to write another new note or read an existing one?</speak>\",\"type\": \"SSML\"},\"shouldEndSession\": false},\"sessionAttributes\": {\"started\": \"true\",\"state\": \"started\"},\"version\": \"1.0\"}";
				exit;
			} elseif($purchaseResult == "ACCEPTED") {
				print "{\"response\": {\"outputSpeech\": {\"ssml\": \"<speak>Would you like to write another new note or read an existing one?</speak>\",\"type\": \"SSML\"},\"shouldEndSession\": false},\"sessionAttributes\": {\"started\": \"true\",\"state\": \"started\"},\"version\": \"1.0\"}";
				exit;
			} elseif($purchaseResult == "ALREADY_PURCHASED") {
				print "{\"response\": {\"outputSpeech\": {\"ssml\": \"<speak>Would you like to write another new note or read an existing one?</speak>\",\"type\": \"SSML\"},\"shouldEndSession\": false},\"sessionAttributes\": {\"started\": \"true\",\"state\": \"started\"},\"version\": \"1.0\"}";
				exit;
			}
		}

		public function beforeLaunch() {
			$this->checkForPurchases();
			$this->logRequest();
		}

		public function checkForPurchases(  ) {
		}

		public function logRequest() {
			$body = $this->alexaRequest->amazonRequestBody;
			$type = $this->alexaRequest->request->type;
			if($type == "IntentRequest") {
				$intent      = $this->alexaRequest->request->intent->name;
				$slots = [];
				foreach ( $this->alexaRequest->request->intent->slots as $slot ) {
					$slots[ $slot->name ] = $slot->value;
				}
				$slots = json_encode($slots);
			} else {
				$intent = null;
				$slots = null;
			}

			$user = $this->userID;
			$session = $this->alexaRequest->session->sessionId;

			$this->db->query("INSERT INTO requests (user,body, session, type, intent, slots) VALUES ('$user','$body', '$session', '$type', '$intent', '$slots')");
			$id = $this->db->query("SELECT id FROM `requests` ORDER BY `requests`.`id` DESC LIMIT 1")->fetchArray()['id'];
			$this->requestID = $id;
		}

		public function handle_LaunchRequest(  ) {
			//file_put_contents('testing.txt', json_encode($this->alexaRequest->session->user->userId));

			$user = $this->getUser($this->alexaRequest->session->user->userId);
			if($user == []) {
				$this->session['new'] = "true";
				$this->addUser($this->alexaRequest->session->user->userId);
			} else {
				$this->session['new'] = "false";
			}

			//$this->session['started'] = 'true';
			$this->session['state'] = 'started';

			$isp = $this->get_isp("Premium");
			if($isp != false && !isset($isp['error'])) {
				if(isset($isp['entitled']) && $isp['entitled'] == "ENTITLED") {
					$this->session['premium'] = true;
				} else {
					$this->session['premium'] = false;
				}
			} else {
				$this->session['premium'] = false;
			}

			if(isset($this->alexaRequest->session->attributes['new']) && $this->alexaRequest->session->attributes['new'] == true) {
				$ssmlGenerator = new SsmlGenerator();
				//Hello and welcome to note pad<break time="0.5s" /> Would you like to write a new note or read an existing one?
				$ssmlGenerator->say( 'Hello thank you for trying note pad ' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( " note pad allows you to save a self destructing note that after being read back to you a single time will be deleted " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( " if you would like to save your notes indefinitely you can purchase our premium upgrade " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( " just ask me what you can buy for more detail " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( " Would you like to write a new note or read an existing one? " );
				$ssml = $ssmlGenerator->getSsml();



				$ssmlGenerator = new SsmlGenerator();
				//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?
				$ssmlGenerator->say( 'I\'m sorry' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( 'I didn\'t quite catch that' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( "Would you like to write a new note or read an existing one?" );
				$ssmlReprompt = $ssmlGenerator->getSsml();

			} else {

				$ssmlGenerator = new SsmlGenerator();
				//Hello and welcome to note pad<break time="0.5s" /> Would you like to write a new note or read an existing one?
				$ssmlGenerator->say( 'Hello and welcome to note pad' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( "Would you like to write a new note or read an existing one?" );
				$ssml = $ssmlGenerator->getSsml();

				$ssmlGenerator = new SsmlGenerator();
				//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?
				$ssmlGenerator->say( 'I\'m sorry' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( 'I didn\'t quite catch that' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( "Would you like to write a new note or read an existing one?" );
				$ssmlReprompt = $ssmlGenerator->getSsml();
			}

			$this->response($ssml, false, $ssmlReprompt);
		}

		public function handleIntent_ReadNote(  ) {
			//if(!isset($this->session['premium'])) {
				$is_premium = $this->is_premium();

				$this->session['state'] = 'reading';

				$note = $this->getNote($this->alexaRequest->session->user->userId);

				if ($note == false) {
					$ssmlGenerator = new SsmlGenerator();
					//Hello and welcome to note pad<break time="0.5s" /> Would you like to write a new note or read an existing one?
					$ssmlGenerator->say( 'You currently have no unread notes' );
					$ssml = $ssmlGenerator->getSsml();
					$reprompt = false;
					$endsession = true;
				} else {
					if($is_premium) {
						if(isset($this->alexaRequest->session->attributes['noteID'])) {
							$lastNote = $this->alexaRequest->session->attributes['noteID'];
							$nextNote = $this->getNextNote($this->alexaRequest->session->user->userId, $lastNote);
							if($nextNote != false) {
								$noteid = $nextNote['id'];
							} else {
								$noteid = false;
							}
						} else {
							$noteid = $note['id'];
						}
						if($noteid == false) {
							$ssmlGenerator = new SsmlGenerator();
							//Hello and welcome to note pad<break time="0.5s" /> Would you like to write a new note or read an existing one?
							$ssmlGenerator->say( 'You currently have no unread notes' );
							$ssml = $ssmlGenerator->getSsml();
							$reprompt = false;
						} else {
							$rowcount = $this->getNextRowCount( $this->alexaRequest->session->user->userId, $noteid );
							if($rowcount > 0) {
								$this->session['state'] = 'readingMultiPremium';
								$this->session['noteID'] = $noteid;

								$ssmlGenerator = new SsmlGenerator();
								//your note reads <break time="0.25s"/> {{ note }} <break time="0.5s" /> Would you like to delete this note?
								$ssmlGenerator->say( 'your note reads ' );
								$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
								$ssmlGenerator->say( $note['note'] );
								$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
								$ssmlGenerator->say( " Would you like to delete this note?" );
								$ssml = $ssmlGenerator->getSsml();

								$reprompt = false;
								$card = new Card();
								$card->title = 'Recently read note';
								$card->text=$note['note'];
							} else {
								$this->session['state'] = 'readingSinglePremium';
								$this->session['noteID'] = $noteid;

								$ssmlGenerator = new SsmlGenerator();
								//your note reads <break time="0.25s"/> {{ note }} <break time="0.5s" /> Would you like to delete this note?
								$ssmlGenerator->say( 'your note reads ' );
								$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
								$ssmlGenerator->say( $note['note'] );
								$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
								$ssmlGenerator->say( " Would you like to delete this note?" );
								$ssml = $ssmlGenerator->getSsml();

								$reprompt = false;
								$card = new Card();
								$card->title = 'Recently read note';
								$card->text=$note['note'];

							}
						}
					} else {
						$noteid = $note['id'];
						$rowcount = $this->getRowCount( $this->alexaRequest->session->user->userId);
						$this->updateNote($noteid);

						if($rowcount > 1) {
							$ssmlGenerator = new SsmlGenerator();
							//your note reads <break time="0.25s"/> {{ note }} <break time="0.5s" /> would you like to hear the next note?
							$ssmlGenerator->say( 'your note reads ' );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
							$ssmlGenerator->say( $note['note'] );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
							$ssmlGenerator->say( " would you like to hear the next note?" );
							$ssml = $ssmlGenerator->getSsml();

							$reprompt = false;
							$card = new Card();
							$card->title = 'Recently read note';
							$card->text=$note['note'];

						} elseif ($this->checkLastAskedReview($this->alexaRequest->session->user->userId) == true) {
							$this->setLastAskedReview($this->alexaRequest->session->user->userId);

							$ssmlGenerator = new SsmlGenerator();
							//your note reads <break time="0.25s"/> {{ note }} <break time="0.5s" /> would you like to hear the next note?
							$ssmlGenerator->say( 'your note reads ' );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
							$ssmlGenerator->say( $note['note'] );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
							$ssmlGenerator->say( " If you like Note Pad " );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
							$ssmlGenerator->say( " please leave us a review on amazon " );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
							$ssmlGenerator->say( " check the link in your alexa app " );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
							$ssmlGenerator->say( " goodbye " );
							$ssml = $ssmlGenerator->getSsml();

							$reprompt = false;
							$card = new Card();
							$card->title = 'Leave a review';
							$card->text="https://www.amazon.com/jake-tc-Note-Pad/dp/B07CQ5FK5L";
						} else {
							$ssmlGenerator = new SsmlGenerator();
							$ssmlGenerator->say( 'your note reads ' );
							$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
							$ssmlGenerator->say( $note['note'] );
							$ssml = $ssmlGenerator->getSsml();

							$reprompt = false;
							$endsession = true;
						}
					}
				}
			//}
			if(!isset($reprompt)) {
				$reprompt = false;
			}
			if(!isset($endsession)) {
				$endsession = false;
			}
			if(!isset($card)) {
				$card = false;
			}
			$this->response($ssml, $endsession, $reprompt, $card);
		}

		public function handleIntent_WriteNote() {
			$this->addUser($this->userID);
			if(!isset($this->session['started'])) {
				$this->session['started'] = 'true';
				$this->session['state']   = 'write';

				$ssmlGenerator = new SsmlGenerator();
				//your note reads <break time="0.25s"/> {{ note }} <break time="0.5s" /> Would you like to delete this note?
				$ssmlGenerator->say( "What would you like your note to say?" );
				$ssml = $ssmlGenerator->getSsml();
			} else {
				if($this->session['started'] == 'true') {
					$this->session['note']  = $this->slots['note'];
					$this->session['state'] = 'writeConfirm';

					//<break time="0.25s" />{{ note }}?
                    $ssmlGenerator = new SsmlGenerator();
                    $ssmlGenerator->say( 'would you like to save your note that reads ' );
                    $ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
                    $ssmlGenerator->say( $this->slots['note'] );
                    $ssml = $ssmlGenerator->getSsml();
                    $reprompt = false;
                    $endsession = false;

                    $card = new Card();
                    $card->title = 'Would you like to save this note?';
                    $card->text=$this->slots['note'];
				} elseif($this->session['started'] == "writeConfirm") {
					$this->session['started'] = 'true';
					$this->session['state']   = 'write';

					//What would you like your note to say?
                    $ssmlGenerator = new SsmlGenerator();
                    $ssmlGenerator->say( 'What would you like your note to say?' );
                    $ssml = $ssmlGenerator->getSsml();

                    $reprompt = false;
                    $endsession = false;
				} else {
					$this->session['started'] = 'true';
					$this->session['state']   = 'read or write';
                    //Would you like to create a new note or hear your unread notes?
                    $ssmlGenerator = new SsmlGenerator();
                    $ssmlGenerator->say( 'Would you like to create a new note or hear your unread notes?' );
                    $ssml = $ssmlGenerator->getSsml();

                    $reprompt = false;
                    $endsession = false;
				}
			}


            if(!isset($reprompt)) {
                $reprompt = false;
            }
            if(!isset($endsession)) {
                $endsession = false;
            }
            if(!isset($card)) {
                $card = false;
            }
            $this->response($ssml, $endsession, $reprompt, $card);
		}

		public function handleIntent_YesIntent() {
			$this->addUser($this->userID);
			if(!isset($this->session['state'])) {
				//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?

				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( "I'm sorry " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " I didn't quite catch that" );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
				$ssmlGenerator->say( " would you like to write a new note or read your unread notes?" );
				$ssml = $ssmlGenerator->getSsml();
				$reprompt = false;
				$endsession = false;

				$card = false;
			} elseif($this->session['state'] == 'writeConfirm') {
				$note  = $this->session['note'];
				$this->addNote( $note, $this->userID );

				if ( $this->checkLastAsked( $this->userID ) == true ) {
					$isp   = $this->get_isp( "Premium" );
					if ( $isp != false  && !isset($isp['error']) && $isp['purchasable'] == "PURCHASABLE") {
						$upselldata = $this->upsell( $isp['productId'] );
						$this->setLastAsked( $this->userID );
						print $upselldata;
						exit;
					} else {
						//Okay <break time="0.25s" /> your note has been added <break time="0.25s" /> Would you like to write another note <break time="0.25s" /> read your notes <break time="0.25s" /> or exit?

						$ssmlGenerator = new SsmlGenerator();
						$ssmlGenerator->say( "Okay " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
						$ssmlGenerator->say( " your note has been added " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
						$ssmlGenerator->say( " Would you like to write another note " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
						$ssmlGenerator->say( " read your notes  " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
						$ssmlGenerator->say( " or exit? " );
						$ssml = $ssmlGenerator->getSsml();
						$reprompt = false;
						$endsession = false;

						$card = false;

					}
				} else {
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( "Okay " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( " your note has been added " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
					$ssmlGenerator->say( " Would you like to write another note " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( " read your notes  " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( " or exit? " );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = false;

					$card = false;
				}
			} elseif ($this->session['state'] == 'reading') {
				$this->handleIntent_ReadNote();
				exit;
			} elseif ($this->session['state'] == 'readingSinglePremium') {
				$this->session['state'] = 'reading';
				$this->updateNote( $this->session['noteID'] );


				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( "Okay " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( " note deleted " );
				$ssml       = $ssmlGenerator->getSsml();
				$reprompt   = false;
				$endsession = true;

				$card = false;
			} elseif ($this->session['state'] == 'readingMultiPremium') {
				$this->session['state'] = 'reading';
				$this->updateNote( $this->session['noteID'] );

				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( "Okay " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( " note deleted " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( " Would you like to hear the next note?" );
				$ssml       = $ssmlGenerator->getSsml();
				$reprompt   = false;
				$endsession = false;

				$card = false;
			} else {
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " I didn't understand what you said " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( " please try again " );
				$ssml       = $ssmlGenerator->getSsml();
				$reprompt   = false;
				$endsession = true;

				$card = false;
			}

			if(!isset($reprompt)) {
				$reprompt = false;
			}
			if(!isset($endsession)) {
				$endsession = false;
			}
			if(!isset($card)) {
				$card = false;
			}
			$this->response($ssml, $endsession, $reprompt, $card);
		}

		public function handleIntent_NoIntent() {
			if(!isset($this->session['state'])) {
				$ssmlGenerator = new SsmlGenerator();
				//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?
				$ssmlGenerator->say( 'I\'m sorry' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( 'I didn\'t quite catch that' );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( "Would you like to write a new note or read an existing one?" );
				$ssml = $ssmlGenerator->getSsml();
				$reprompt = false;
				$endsession = false;

				$card = false;
			} else {
				if ( $this->session['state'] == 'writeConfirm' ) {
					if ( $this->checkLastAskedReview( $this->userID ) == true ) {
						$this->setLastAskedReview( $this->userID );

						$ssmlGenerator = new SsmlGenerator();
						//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?
						$ssmlGenerator->say( 'If you like Note Pad ' );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
						$ssmlGenerator->say( ' please leave us a review on amazon ' );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
						$ssmlGenerator->say( " check the link in your alexa app    " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
						$ssmlGenerator->say( ' goodbye ' );
						$ssml       = $ssmlGenerator->getSsml();
						$reprompt   = false;
						$endsession = true;

						$card          = new Card();
						$card->title   = "Leave a review";
						$card->text = "https://www.amazon.com/jake-tc-Note-Pad/dp/B07CQ5FK5L";
					} else {
						$ssmlGenerator = new SsmlGenerator();
						$ssmlGenerator->say( ' Okay Goodbye ' );
						$ssml       = $ssmlGenerator->getSsml();
						$reprompt   = false;
						$endsession = true;
						$card       = false;
					}
				} elseif ( $this->session['state'] == 'reading' ) {
					if ( $this->checkLastAskedReview( $this->userID ) == true ) {
						$this->setLastAskedReview( $this->userID );

						$ssmlGenerator = new SsmlGenerator();
						//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?
						$ssmlGenerator->say( 'If you like Note Pad ' );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
						$ssmlGenerator->say( ' please leave us a review on amazon ' );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
						$ssmlGenerator->say( " check the link in your alexa app    " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
						$ssmlGenerator->say( ' goodbye ' );
						$ssml       = $ssmlGenerator->getSsml();
						$reprompt   = false;
						$endsession = true;

						$card          = new Card();
						$card->title   = "Leave a review";
						$card->text = "https://www.amazon.com/jake-tc-Note-Pad/dp/B07CQ5FK5L";
					} else {
						$ssmlGenerator = new SsmlGenerator();
						$ssmlGenerator->say( ' Okay Goodbye ' );
						$ssml       = $ssmlGenerator->getSsml();
						$reprompt   = false;
						$endsession = true;
						$card       = false;
					}
				} elseif ( $this->session['state'] == 'readingSinglePremium' ) {
					$this->session['state'] = 'reading';
					$ssmlGenerator          = new SsmlGenerator();
					$ssmlGenerator->say( ' Okay note saved ' );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = true;
					$card       = false;
				} elseif ( $this->session['state'] == 'readingMultiPremium' ) {
					$this->session['state'] = 'reading';

					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( ' Okay note saved ' );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( ' note saved ' );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
					$ssmlGenerator->say( ' Would you like to hear the next note? ' );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = false;
					$card       = false;
				} else {
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " I didn't understand what you said " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( " please try again " );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = true;

					$card = false;
				}
			}




			if(!isset($reprompt)) {
				$reprompt = false;
			}
			if(!isset($endsession)) {
				$endsession = false;
			}
			if(!isset($card)) {
				$card = false;
			}
			$this->response($ssml, $endsession, $reprompt, $card);

		}

		public function handleIntent_startWrite() {
			$this->addUser( $this->userID );
			$isp = $this->get_isp( "Premium" );
			$this->is_premium();
			if ( ! isset( $this->session['state'] ) ) {
				$set = 'no';
			} else {
				if ( $this->session['state'] == 'write' ) {
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " I didn't understand what you said " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( " please try again " );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = true;

					$card = false;
				} elseif ( $this->session['state'] == 'reading' ) {
					$this->handleIntent_ReadNote();
				} elseif ( $this->session['state'] == 'started' || $this->session['state'] == 'writeConfirm' ) {
					//What would you like your note to say?
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " What would you like your note to say? " );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = false;

					$card = false;
				}
			}
			if ( ! isset( $this->session['started'] ) ) {
				$this->session['started'] = 'true';
				//Welcome to note pad <break time="0.5s"/> What would you like your note to say?
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " Welcome to note pad " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
				$ssmlGenerator->say( " What would you like your note to say? " );
				$ssml       = $ssmlGenerator->getSsml();
				$reprompt   = false;
				$endsession = false;

				$card = false;
			} elseif($this->session['started'] == 'true') {
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " I didn't understand what you said " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( " please try again " );
				$ssml       = $ssmlGenerator->getSsml();
				$reprompt   = false;
				$endsession = true;

				$card = false;
			} else {
				$this->session['started'] = 'true';
				$this->session['state']   = 'read or write';
				//Would you like to create a new note or hear your unread notes?
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " Would you like to create a new note or hear your unread notes? " );
				$ssml       = $ssmlGenerator->getSsml();
				$reprompt   = false;
				$endsession = false;

				$card = false;
			}

			if(!isset($reprompt)) {
				$reprompt = false;
			}
			if(!isset($endsession)) {
				$endsession = false;
			}
			if(!isset($card)) {
				$card = false;
			}
			$this->response($ssml, $endsession, $reprompt, $card);
		}

		public function handleIntent_RorW() {
			if($this->session['state'] == 'read or write') {
				if ( $this->slots['answer'] == "write" ) {
					//What would you like your note to say?
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " What would you like your note to say? " );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = false;

					$card = false;
				} elseif ( $this->slots['answer'] == "read" ) {
					$this->handleIntent_ReadNote();
					exit;
				} elseif ( $this->session['state'] == 'reading' ) {
					$this->handleIntent_ReadNote();
					exit;
				} else {
					//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " I'm sorry " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( " I didn't quite catch that " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
					$ssmlGenerator->say( " would you like to write a new note, or read your unread notes? " );
					$ssml       = $ssmlGenerator->getSsml();
					$reprompt   = false;
					$endsession = false;

					$card = false;
				}
			} else {
				//I'm sorry <break time="0.25s"/> I didn't quite catch that <break time="0.5s"/> would you like to write a new note, or read your unread notes?
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " I'm sorry " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( " I didn't quite catch that " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
				$ssmlGenerator->say( " would you like to write a new note, or read your unread notes? " );
				$ssml       = $ssmlGenerator->getSsml();
				$reprompt   = false;
				$endsession = false;

				$card = false;
			}

			if ( ! isset( $reprompt ) ) {
				$reprompt = false;
			}
			if ( ! isset( $endsession ) ) {
				$endsession = false;
			}
			if ( ! isset( $card ) ) {
				$card = false;
			}
			$this->response( $ssml, $endsession, $reprompt, $card );
		}

		public function handleIntent_HelpIntent() {
			//Hello <break time="0.25s"/> I am note pad, the self deleting notes app <break time="0.5s"/> I can write a note for you <break time="0.25s"/> which can be red aloud at a later date <break time="0.25s"/> then automatically deleted. To do this, ask me to write a note, or ask me to read your notes <break time="0.5s"/> Now <break time="0.25s"/> would you like me to read your notes or write a note?
			$ssmlGenerator = new SsmlGenerator();
			$ssmlGenerator->say( " Hello " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
			$ssmlGenerator->say( " I am note pad, the self deleting notes app " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
			$ssmlGenerator->say( " I can write a note for you " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
			$ssmlGenerator->say( " which can be red aloud at a later date " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
			$ssmlGenerator->say( "  then automatically deleted " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
			$ssmlGenerator->say( "  To do this, ask me to write a note, or ask me to read your notes " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM );
			$ssmlGenerator->say( "  Now " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
			$ssmlGenerator->say(' would you like me to read your notes or write a note? ');
			$ssml       = $ssmlGenerator->getSsml();

			$ssmlGenerator = new SsmlGenerator();
			$ssmlGenerator->say( " Would you like to write a new note or read an existing one? " );
			$reprompt       = $ssmlGenerator->getSsml();
			$endsession = false;
			$card = false;

			$this->response( $ssml, $endsession, $reprompt, $card );
		}

		public function handle_SessionEndedRequest() {
			$ssmlGenerator = new SsmlGenerator();
			$ssmlGenerator->say( " Okay " );
			$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
			$ssmlGenerator->say( " goodbye " );
			$ssml       = $ssmlGenerator->getSsml();
			$endsession = true;
			$reprompt = false;
			$card = false;

			$this->response( $ssml, $endsession, $reprompt, $card );
		}

		public function handleIntent_CancelIntent() {
			if($this->checkLastAskedReview($this->userID) == true) {
				$this->setLastAskedReview( $this->userID );

				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " If you like Note Pad " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " please leave us a review on amazon " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
				$ssmlGenerator->say( " check the link in your alexa app " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " goodbye " );
				$ssml = $ssmlGenerator->getSsml();
				$endsession = true;
				$reprompt = false;
				$card = new Card();
				$card->title = 'Leave a review';
				$card->text="https://www.amazon.com/jake-tc-Note-Pad/dp/B07CQ5FK5L";
			} else {
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " okay goodbye " );
				$ssml = $ssmlGenerator->getSsml();
				$endsession = true;
				$reprompt = false;
				$card = false;
			}
			$this->response( $ssml, $endsession, $reprompt, $card );
		}

		public function handleIntent_StopIntent() {
			$this->handleIntent_CancelIntent();
		}

		public function handleIntent_CancelSkillItemIntent() {
			$isp = $this->get_isp($this->slots['ProductName']);
			if($isp != False) {
				if(!isset($isp['error']) &&  $this->slots['ProductName'] == "premium" ) {
					$token = $this->addUserToken( $this->userID );
					$data   = "{
					'version': '1.0',
						  'response': {
						'directives': [
								  {
									  'type': 'Connections.SendRequest',
									  'name': 'Cancel',
									  'payload': {
									  'InSkillProduct': {
										  'productId': " . $isp['productId'] . "
												 },
									   },
									  'token': $token
								  }
							  ],
							  'shouldEndSession': true
						  }
						}";
					print $data;
					exit;
				} else {
					$this->session['premium'] = false;
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " Sorry " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
					$ssmlGenerator->say( " I couldn't find that product " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
					$ssmlGenerator->say( " please try again later " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
					$ssmlGenerator->say( " Would you like to write a new note or read an existing one? " );
					$ssml = $ssmlGenerator->getSsml();
					$endsession = false;
					$reprompt = false;
					$card = false;
				}
			} else {
				$this->session['started'] = 'true';
				$this->session['state']   = 'started';
				$this->addUser( $this->userID);
				$this->session['premium'] = false;
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " Sorry " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " I couldn't find that product " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " please try again later " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
				$ssmlGenerator->say( " Would you like to write a new note or read an existing one? " );
				$ssml = $ssmlGenerator->getSsml();
				$endsession = false;
				$reprompt = false;
				$card = false;
			}

			if ( ! isset( $reprompt ) ) {
				$reprompt = false;
			}
			if ( ! isset( $endsession ) ) {
				$endsession = false;
			}
			if ( ! isset( $card ) ) {
				$card = false;
			}
			$this->response( $ssml, $endsession, $reprompt, $card );

		}

		public function handleIntent_BuySkillItemIntent(  ) {
			$isp = $this->get_isp( "Premium" );
			if ( $isp != false ) {
				if ( !isset( $isp['error'] ) ) {
					if ( $isp['entitled'] == "ENTITLED" ) {
						$this->session['premium'] = true;
					} else {
						$this->session['premium'] = false;
					}
					$token = $this->addUserToken( $this->userID );
					$data  = "{
					'version': '1.0',
						  'response': {
						'directives': [
								  {
									  'type': 'Connections.SendRequest',
									  'name': 'Buy',
									  'payload': {
									  'InSkillProduct': {
										  'productId': " . $isp['productId'] . "
												 },
									   },
									  'token': $token
								  }
							  ],
							  'shouldEndSession': true
						  }
						}";
					print $data;
					exit;
				} else {
						$this->session['premium'] = false;
						$ssmlGenerator = new SsmlGenerator();
						$ssmlGenerator->say( " Sorry " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
						$ssmlGenerator->say( " hat product is currently unavailable " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
						$ssmlGenerator->say( " please try again later " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
						$ssmlGenerator->say( " Would you like to write a new note or read an existing one? " );
						$ssml = $ssmlGenerator->getSsml();
						$endsession = false;
						$reprompt = false;
						$card = false;
				}
			} else {
				$this->session['premium'] = false;
				$this->session['started'] = true;
				$this->session['state'] = 'started';
				$this->addUser($this->userID);
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " Sorry " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " hat product is currently unavailable " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " please try again later " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_MEDIUM);
				$ssmlGenerator->say( " Would you like to write a new note or read an existing one? " );
				$ssml = $ssmlGenerator->getSsml();
				$endsession = false;
				$reprompt = false;
				$card = false;
			}


			if ( ! isset( $reprompt ) ) {
				$reprompt = false;
			}
			if ( ! isset( $endsession ) ) {
				$endsession = false;
			}
			if ( ! isset( $card ) ) {
				$card = false;
			}
			$this->response( $ssml, $endsession, $reprompt, $card );
		}

		public function handleIntent_WhatCanIBuy() {
			$isp = $this->get_isp("premium");
			if($isp != false) {
				if ( isset($isp['entitled'])) {
					if ( $isp['entitled'] == "ENTITLED" ) {
						//return statement( "<speak>Good news <break time=\"0.25s\" /> you have already subscribed to Premium <break time=\"0.5s\" /> You can save notes for as long as you want and have them ready back to you as many times as you like</speak>" )
						$ssmlGenerator = new SsmlGenerator();
						$ssmlGenerator->say( " Good news " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
						$ssmlGenerator->say( "  you have already subscribed to Premium " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
						$ssmlGenerator->say( " You can save notes for as long as you want and have them ready back to you as many times as you like " );
						$ssml = $ssmlGenerator->getSsml();
						$endsession = true;
						$reprompt = false;
						$card = false;
					} else {
						$this->upsellSingle( $isp['productId'] );
						exit;
					}
				} else {
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " Currently there are no products availible for purchase  " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
					$ssmlGenerator->say( " this may be because they haven't been released in your location just yet  " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
					$ssmlGenerator->say( " please come back and check later " );
					$ssml = $ssmlGenerator->getSsml();
					$endsession = true;
					$reprompt = false;
					$card = false;
				}
			} else {
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " Currently there are no products availible for purchase  " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " this may be because they haven't been released in your location just yet  " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK);
				$ssmlGenerator->say( " please come back and check later " );
				$ssml = $ssmlGenerator->getSsml();
				$endsession = true;
				$reprompt = false;
				$card = false;
			}

			if ( ! isset( $reprompt ) ) {
				$reprompt = false;
			}
			if ( ! isset( $endsession ) ) {
				$endsession = false;
			}
			if ( ! isset( $card ) ) {
				$card = false;
			}
			$this->response( $ssml, $endsession, $reprompt, $card );
		}

		public function handleIntent_WhatDidIBuy() {
			$isp = $this->get_isp("premium");
			if($isp != false) {
				if ( isset( $isp['entitled'] ) ) {
					if ( $isp['entitled'] == "ENTITLED" ) {
						$ssmlGenerator = new SsmlGenerator();
						$ssmlGenerator->say( " You have purchased the Premium subscription  " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
						$ssmlGenerator->say( " this allows you to save notes indefinitely  " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
						$ssmlGenerator->say( " and have them read back as many times as you like " );
						$ssml       = $ssmlGenerator->getSsml();
						$endsession = true;
						$reprompt   = false;
						$card       = false;
					} else {
						$ssmlGenerator = new SsmlGenerator();
						$ssmlGenerator->say( " Currently you have not made any purchases in this skill   " );
						$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
						$ssmlGenerator->say( " ask me what is available to purchase to see what you can buy  " );
						$ssml       = $ssmlGenerator->getSsml();
						$endsession = true;
						$reprompt   = false;
						$card       = false;
					}
				} else {
					$ssmlGenerator = new SsmlGenerator();
					$ssmlGenerator->say( " Currently you have not made any purchases in this skill   " );
					$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
					$ssmlGenerator->say( " ask me what is available to purchase to see what you can buy  " );
					$ssml       = $ssmlGenerator->getSsml();
					$endsession = true;
					$reprompt   = false;
					$card       = false;
				}
			} else {
				$ssmlGenerator = new SsmlGenerator();
				$ssmlGenerator->say( " Currently you have not made any purchases in this skill   " );
				$ssmlGenerator->pauseStrength( SsmlGenerator::BREAK_STRENGTH_WEAK );
				$ssmlGenerator->say( " ask me what is available to purchase to see what you can buy  " );
				$ssml       = $ssmlGenerator->getSsml();
				$endsession = true;
				$reprompt   = false;
				$card       = false;
			}

			if ( ! isset( $reprompt ) ) {
				$reprompt = false;
			}
			if ( ! isset( $endsession ) ) {
				$endsession = false;
			}
			if ( ! isset( $card ) ) {
				$card = false;
			}
			$this->response( $ssml, $endsession, $reprompt, $card );
		}

		public function handle_session_ended() {

		}

		public function upsellSingle($productID) {
			$token = $this->addUserToken($this->userID);
			$data = "{
				'version': '1.0',
				'response': {
							'directives': [
						  {
							  'type': 'Connections.SendRequest',
							  'name': 'Upsell',
							  'payload': {
							  'InSkillProduct': {
								  'productId': $productID
										 },
										'upsellMessage': 'You can purchase the Premium subscription to the Alexa notes app. By upgrading to the premium subscription, you get access to more features, like the ability to save notes indefinidelty and have them read back as many times as you want. Would you like to learn more?'
							   },
							  'token': $token
						  }
					  ],
					  'shouldEndSession': True
				  }
				}";
			print $data;
			exit;
		}


		public function addNote( $note, $userID ) {
			$this->db->query("INSERT INTO notes (userid, note, time_added, seen) VALUES ('$userID', '$note', " . time() . ", 0)");
		}

		public function checkLastAsked( $userID ) {
			$apiEndPoint = $this->alexaRequest->context->system->apiEndpoint;
			$apiAccessToken = $this->alexaRequest->context->system->apiAccessToken;
			if(!isset($this->session['checkLastAsked'])) {
				$user = $this->db->query( "SELECT * FROM users WHERE userid='$userID'")->fetchArray();
				if ($user['asked_premium'] != null) {
					$theDate = strtotime($user['asked_premium']);
					if ( $theDate < strtotime( '-30 days' ) ) {
						$this->session['checkLastAsked'] = true;
						return true;
					} else {
						$this->session['checkLastAsked'] = false;
						return false;
                    }
				} else {
					$this->session['checkLastAsked'] = true;
					return true;
				}
			} else {
				return $this->session['checkLastAsked'];
			}
		}

		public function setLastAsked( $userid ) {
			$theDate = date('D M d H:i:s Y');
			$this->db->query("UPDATE users SET asked_premium='$theDate' WHERE userid='$userid'");
		}


		public function setLastAskedReview( $userID ) {
			$theDate = date('D M d H:i:s Y');
			$this->db->query("UPDATE users SET asked_review='$theDate' WHERE userid='$userID'");
		}

		public function checkLastAskedReview( $userID ) {
			if(!isset($this->session['checkLastAskedReview'])) {
				$user = $this->db->query( "SELECT * FROM users WHERE userid='$userID'" )->fetchArray();
				if ( $user['asked_review'] != null ) {
					//Thu Nov 15 23:01:49 2018
					//$theDate = date('D M d H:i:s Y');
					$thedate = strtotime( $user['asked_review'] );
					if ( $thedate < strtotime( '-30 days' ) ) {
						$this->session['checkLastAskedReview'] = true;
						return true;
					} else {
						$this->session['checkLastAskedReview'] = false;
						return false;
					}
				} else {
					$this->session['checkLastAskedReview'] = true;
					return true;
				}
			} else {
				return $this->session['checkLastAskedReview'];
			}
		}

		public function updateNote( $noteID ) {
			$this->db->query("UPDATE notes SET seen=1 WHERE id=$noteID");
		}

		public function getNextRowCount( $userid, $noteid ) {
			$notes = $this->db->query("SELECT note, id FROM notes WHERE userid='$userid' AND seen=0 AND id > $noteid")->fetchAll();
			return count($notes);
		}

		public function getRowCount( $userid ) {
			return count($this->db->query("SELECT note, id FROM notes WHERE userid='$userid' AND seen=0")->fetchAll());
		}

		public function getNextNote( $userID, $noteID ) {
			try {
				$note = $this->db->query( "SELECT note, id FROM notes WHERE userid='$userID' AND seen=0 AND id > $noteID" )->fetchArray();
			} catch ( Exception $e ) {
				$note = false;
			}

			return $note;
		}

		public function getNote( $userid ) {
			try {
				$note = $this->db->query( "SELECT note, id FROM notes WHERE userid='$userid' AND seen=0 LIMIT 1" )->fetchArray();
			} catch ( Exception $e ) {
				file_put_contents('testing.txt', "SELECT note, id FROM notes WHERE userid='$userid' AND seen=0 LIMIT 1");
				file_put_contents('testing.txt', $e->getMessage(), FILE_APPEND);
				$note = false;
			}
			return $note;
		}

		public function is_premium() {
			if(!isset($this->session['premium'])) {
				$isp = $this->get_isp( 'Premium' );
				if ( $isp != false && isset( $isp['entitled'] ) && $isp['entitled'] == "ENTITLED" ) {
					$this->session['premium'] = true;

					return true;
				} else {
					$this->session['premium'] = false;

					return false;
				}
			} else {
				return $this->session['premium'];
			}
		}

		public function getUser( $userID ) {

			$user = $this->db->query("SELECT * FROM users WHERE userid='" . $userID . "'")->fetchAll();
			file_put_contents('debug.txt', json_encode($user), FILE_APPEND);
			file_put_contents('debug.txt', "\r\n\r\n", FILE_APPEND);
			if($user = []) {
				$this->addUser($userID);
				$user = $this->db->query("SELECT * FROM users WHERE userid='" . $userID . "'")->fetchAll();

			}
			return $user;
		}

		function addUser($userID) {
			$user = $this->getUser($userID);
			if($user == []) {
				//file_put_contents('testing.txt', "INSERT INTO users (userid) VALUES ('" . $userID . "')");
				$this->db->query("INSERT INTO users (userid) VALUES ('" . $userID . "')");
			}
		}

		public function create_context($headers) {
			// Create a stream
			$opts = array(
				'http' => array(
					'method' => "GET",
					'header' => $headers
				)
			);


			$context = stream_context_create( $opts );
			return $context;
		}

		public function get_isp($ProductName=false) {
			$apiEndPoint = $this->alexaRequest->context->system->apiEndpoint;
			$apiAccessToken = $this->alexaRequest->context->system->apiAccessToken;
			if($apiEndPoint != "") {
				$apiEndPointDomain = str_replace( "https://", "", $apiEndPoint );
				$the_url           = $apiEndPoint . "/v1/users/~current/skills/~current/inSkillProducts/";
				$local             = $this->alexaRequest->request->locale;

				$context = $this->create_context("authorization:  Bearer $apiAccessToken\r\n" .
				                                 "Accept-Language: $local\r\n" .
				                                 "Host: $apiEndPointDomain\r\n");
				try {
					$file    = file_get_contents( $the_url, false, $context );
				} catch (Exception $e) {
					$file = false;
				}
				//{"inSkillProducts":[{"productId":"amzn1.adg.product.5dab7a28-e572-476f-a520-0f01d2b9c8bc","referenceName":"Premium","type":"SUBSCRIPTION","name":"Premium","summary":"With our Premium subscription, can store notes for as long as you want.","entitled":"NOT_ENTITLED","entitlementReason":"NOT_PURCHASED","purchasable":"PURCHASABLE","activeEntitlementCount":0,"purchaseMode":"TEST"}],"nextToken":null,"truncated":false}
				if ( $file !== false ) {
					$isps = json_decode( $file );
					//file_put_contents('testing.txt', $file);
					if ( $isps != null && isset($isps->inSkillProducts[0])) {
						$ispName = $isps->inSkillProducts[0]->name;
						if ( $ProductName == false) {
							return false;
						}
						if ( $ProductName == "Unset" || strtolower($ProductName) != strtolower($ispName)) {
							return false;
						}
						$ispSummary       = $isps->inSkillProducts[0]->summary;
						$ispReferenceName = $isps->inSkillProducts[0]->referenceName;
						$ispProductId     = $isps->inSkillProducts[0]->productId;
						$ispType          = $isps->inSkillProducts[0]->type;
						$ispEntitled      = $isps->inSkillProducts[0]->entitled;
						/*$payload          = {
							"authorization":"Bearer " + apiAccessToken, "Accept-Language":local, "Host":apiEndPointDomain}*/
						$the_second_url = $apiEndPoint . "/v1/users/~current/skills/~current/inSkillProducts/" . $ispProductId;

						$context = $this->create_context("authorization:  Bearer $apiAccessToken\r\n" .
						                                 "Accept-Language: $local\r\n" .
						                                 "Host: $apiEndPointDomain\r\n");
						try {
							$file    = file_get_contents( $the_second_url, false, $context );
						} catch (Exception $e) {
							$file = false;
						}

						if ( $file ) {
							$prod = json_decode($file);
							file_put_contents('body.txt', $file);
							$ispsPurchasable = $prod->purchasable;
							if ( $ispsPurchasable == "PURCHASABLE" ) {
								return [
									"name" => $ispName,
									"summary" => $ispSummary,
									"referenceName" => $ispReferenceName,
									"productId" => $ispProductId,
									"type" => $ispType,
									"entitled" => $ispEntitled,
									"purchasable" => $ispsPurchasable
								];
							} else if ( $ispEntitled == "ENTITLED" ) {
								return [
									"name" => $ispName,
									"summary" => $ispSummary,
									"referenceName" => $ispReferenceName,
									"productId" => $ispProductId,
									"type" => $ispType,
									"entitled" => $ispEntitled,
									"purchasable" => $ispsPurchasable
								];
							}else if ( $ispsPurchasable == "NOT_PURCHASABLE" ) {
								return [ "error" => "Not Purchasable" ];
							}else {
								return false;
							}
						} else {
							return false;
						}
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		public function addUserToken( $userid ) {
			$user = $this->getUser($userid);
			if(!isset($user['token']) || $user['token'] == null) {
				$now   = time();
				$token = md5($now);
				$this->db->query("UPDATE users SET token='$token' WHERE userid='$userid'" );
				return $token;
			} else {
				return $user['token'];
			}
		}

		public function response($ssml, $endsession = false, $reprompt = false, $card = false) {
			$response = new ResponseHelper();
			$response->respondSsml($ssml);
			if($reprompt) {
				$response->repromptSsml($reprompt);
			}
			foreach($this->session as $key => $val) {
				$response->addSessionAttribute($key, $val);
			}

			if($card != false) {
				$response->card($card);
			}

			$response->responseBody->shouldEndSession = $endsession;
			$this->logResponse($ssml, $card, $endsession, $response);
			print json_encode($response->getResponse());
			exit;
		}


		/**
		 * @param $ssml string
		 * @param $card Card
		 * @param $endsession boolean
		 * @param $response ResponseHelper
		 *
		 * @throws Exception
		 */
		public function logResponse($ssml, $card, $endsession, $response) {
			$user = $this->userID;
			$session = $this->alexaRequest->session->sessionId;
			$requestID = $this->requestID;
			$body = json_encode($response->getResponse());
			if($card != false) {
				$cardtext = json_encode($card);
			} else {
				$cardtext = null;
			}
			$ssml = addslashes($ssml);
			if($endsession == true) {
				$endsession = 1;
			} else {
				$endsession = 0;
			}

			file_put_contents('testing.txt', "INSERT INTO response (user, body, session, requestID, ssml, card, endsession ) VALUES ('$user','$body', '$session', '$requestID', '$ssml', '$cardtext', '$endsession')");
			$this->db->connection->query("INSERT INTO response (user, body, session, requestID, ssml, card, endsession ) VALUES ('$user','$body', '$session', '$requestID', '$ssml', '$cardtext', '$endsession')");
		}


		public function upsell($productID) {
			$token = $this->addUserToken($this->userID);
			$data = "{
						'version': '1.0',
				  'response': {
							'directives': [
						  {
							  'type': 'Connections.SendRequest',
							  'name': 'Upsell',
							  'payload': {
							  'InSkillProduct': {
								  'productId': $productID
										 },
										'upsellMessage': 'Your note has been saved, ready to be read back only a single time. If you upgrade to the Premium subscription, you will gain the ability to save your notes indefinetly, and have them read back to you as many times as you want. Would you like to learn more?'
							   },
							  'token': $token
						  }
					  ],
					  'shouldEndSession': True
				  }
				};";
			return $data;
		}
	}