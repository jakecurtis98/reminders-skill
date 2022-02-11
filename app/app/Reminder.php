<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    public function user() {
    	return $this->belongsTo('App\User');
    }

	public function getUser() {
		return $this->user()->get()->first();
    }
	
	public function confirmations() {
		return $this->hasMany('App\ReminderConfirmation');
	}
	
	public function getConfirmations() {
		return $this->confirmations()->get();
    }
	public function getLatestConfirmation() {
		return $this->confirmations()->orderBy('reminder_time', 'DESC')->get()->first();
    }
}
