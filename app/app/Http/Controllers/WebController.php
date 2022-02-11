<?php

namespace App\Http\Controllers;

use App\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebController extends Controller
{
	public function index() {
		return view('dashboard');
    }

	public function viewReminders() {
		$user = Auth::user();
		$reminders = resolve('App\Http\Controllers\ReminderController')->getUserAPIReminders($user->alexa_id);
		return view('reminders.list')->with('reminders', $reminders);
    }

	public function editReminder( Reminder $reminder ) {
		if(Auth::user()->getAuthIdentifier() == $reminder->user_id) {
			$data['reminder'] = $reminder;
			$data['user'] = Auth::user();
			return view('reminders.edit', $data);
		} else {
			return abort(401);
		}
    }

	public function updateReminder( Request $request, Reminder $reminder ) {
		if(Auth::user()->getAuthIdentifier() == $reminder->user_id) {
			$reminder->title     = $request->title;
			$reminder->name      = $request->name;
			$reminder->frequency = $request->frequency;
			$reminder->time      = $request->time;
			$reminder->save();

			resolve( 'App\Http\Controllers\ReminderController' )->updateAPIReminder( $reminder );

			return back()->with( 'updated', 1 );
		} else {
			return abort(401);
		}
	}

	public function confirmDeleteReminder( Reminder $reminder ) {
		if(Auth::user()->getAuthIdentifier() == $reminder->user_id) {
			$data = ["reminder" => $reminder];
			return view('reminders.delete', $data);
		} else {
			return abort(401);
		}
	}

	public function DeleteReminder( Reminder $reminder ) {
		if(Auth::user()->getAuthIdentifier() == $reminder->user_id) {
			resolve( 'App\Http\Controllers\ReminderController' )->deleteAPIReminder( $reminder );
			$reminder->delete();

			return redirect(asset('/reminders'));
		} else {
			return abort(401);
		}
	}
}
