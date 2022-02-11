<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
	public function showLinkForm(Request $request) {
		Session::put("isLinking", true);
		Session::put("client_id", $request->client_id);
		Session::put("token_type", $request->token_type);
		Session::put("the_state", $request->state);
		Session::put("redirect_uri", $request->redirect_uri);
		Session::save();
		if(Auth::user()) {
			return view( 'users.linkConfirm' );
		} else {
			return view( 'users.linkForm' );
		}
    }

	public function confirmLink() {
		/** @var User $user */
		$user              = Auth::user();
		$id                = uniqid();
		$redirect_url      = $this->getRedirectUrl($id);
		$user->alexa_token = $id;
		$user->save();
		return redirect($redirect_url);
    }

	public function getRedirectUrl( $id ) {
		//state, access_token, and token_type
		$url = Session::get('redirect_uri') . "#";
		$url .= "state=" . Session::get('the_state');
		$url .= "&access_token=" . $id;
		$url .= "&token_type=Bearer";
		return $url;
    }
}
