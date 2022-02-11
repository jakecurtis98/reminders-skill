<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

	public function confirmations() {
		return $this->hasMany('App\ReminderConfirmation');
    }
	public function hasUnconfirmedReminders() {
		return $this->confirmations()->where('confirmed', 0)->count() > 0;
	}

	public function reminders() {
		return $this->hasMany('App\Reminder');
    }

    public function hasReminders() {
		return $this->reminders()->count() > 0;
    }
}
