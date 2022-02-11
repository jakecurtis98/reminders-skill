<?php
use \Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/link', 'UserController@showLinkForm')->name('link');
Route::get('/link/confirm', 'UserController@confirmLink');

Route::match(['get', 'post'], '/botman', 'BotManController@handle');

Route::get('/botman/tinker', 'BotManController@tinker');

//Route::get('/getUserAPIReminders/{userID}', 'ReminderController@getUserAPIReminders');
	Route::get('/privacy-policy', function () {
		return view::make('privacy');
	});

	Route::get('/terms', function () {
		return view::make('privacy');
	});

Route::group(['middleware' => 'auth'], function () {
	Route::get( '/', 'WebController@index');
	Route::get( '/reminders/{reminder}', 'WebController@editReminder');
	Route::post( '/reminders/{reminder}', 'WebController@updateReminder');
	Route::get( '/reminders/{reminder}/delete', 'WebController@confirmDeleteReminder');
	Route::get( '/reminders/{reminder}/delete/confirm', 'WebController@DeleteReminder');
	Route::get( '/reminders', 'WebController@viewReminders');
});

Auth::routes();
