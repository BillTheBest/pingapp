<?php defined('SYSPATH') or die('No direct access allowed.');

class Controller_Ivr_Twilio extends Controller {

	public function action_index()
	{
		header('Content-type: text/xml');

		echo '<?xml version="1.0" encoding="UTF-8"?>
		<Response>
			<Gather action="'.URL::base().'ivr/twilio/gather" method="POST" numDigits="1" timeout="15">
				<Say voice="woman">Thank you for calling PingApp.com</Say>
				<Pause length="1"/>
				<Say voice="woman">If you are okay, please press 1 then press pound or hash</Say>
				<Pause length="1"/>
				<Say voice="woman">If you are not okay, please press 2 then press pound or hash</Say>
			</Gather>
			<Say voice="woman">We didn\'t receive any input. Goodbye!</Say>
			<Hangup/>
		</Response>
		';		
	}

	public function action_gather()
	{
		if ($this->request->method() == 'POST')
		{
			$provider = PingApp_SMS_Provider::instance();

			// Authenticate the request
			$options =  $provider->options();
			if ($this->request->post('AccountSid') !== $options['account_sid'])
			{
				// Could not authenticate the request?
				throw new HTTP_Exception_403();
			}
			
			// Remove Non-Numeric characters because that's what the DB has
			$to = preg_replace("/[^0-9,.]/", "", $this->request->post('To'));
			$from  = preg_replace("/[^0-9,.]/", "", $this->request->post('From'));
			$sender = preg_replace("/[^0-9,.]/", "", PingApp_SMS_Provider::$sms_sender);

			if ( ! $to OR strrpos($to, $sender) === FALSE )
			{
				Kohana::$log->add(Log::ERROR, __("':to' was not used to send a message to ':from'",
				    array(':to' => $to, ':from' => $from)));
				return;
			}
			
			$digits  = $this->request->post('Digits');
			if ($digits == 1)
			{
				$message_text = 'IVR: Okay';
			}
			else if ($digits == 2)
			{
				$message_text = 'IVR: Not Okay';
			}
			else
			{
				// HALT
				Kohana::$log->add(Log::ERROR, __("':digits' is not a valid IVR response", array(":digits" => $digits)));
				return;
			}
	
			// Is the sender of the message a registered contact?
			$contact = Model_Contact::get_contact($from, 'phone');
			if ( ! $contact)
			{
				// HALT
				Kohana::$log->add(Log::ERROR, __("':from' is not registered as a contact", array(":from" => $from)));
				return;
			}
			
			// Use the last id of the ping to tag the pong
			// TODO: Review
			$ping = DB::select(array(DB::expr('MAX(id)'), 'ping_id'))
				->from('pings')
				->where('contact_id', '=', $contact->id)
				->where('type', '=', 'phone')
				->where('status', '=', 'pending')
				->execute()
				->as_array();
			
			// Record the pong
			if ( $ping[0]['ping_id'] )
			{
				// Load the pong
				$ping = ORM::factory('Ping', $ping[0]['ping_id']);
				
				// Mark the ping as replied
				$ping->set('status', 'replied')->save();
				
				$pong = ORM::factory('Pong')
					->values(array(
						'content' => $message_text,
						'contact_id' => $contact->id,
						'type' => 'voice',
						'ping_id' => $ping->id
					))
					->save();
				
				// Lets parse the message for OK/NOT OKAY indicators
				PingApp_Status::parse($contact, $pong, $message_text);
			}
			else
			{
				Kohana::$log->add(Log::ERROR, __("There is no record of ':from' having been pinged",
					array(":from" => $from)));
			}
		}
	}
}