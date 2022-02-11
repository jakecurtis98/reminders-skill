<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReminderConfirmation extends Model
{
    
    public function user() {
    	return $this->belongsTo('App\User');
    }

	public function getUser() {
		return $this->user()->get()->first();
    }
	
    public function reminder() {
    	return $this->belongsTo('App\Reminder');
    }

	public function getReminder() {
		return $this->reminder()->get()->first();
    }
}
