<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\LoginNeedsVerfication;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function submit(Request $request)
    {

        // validate phone number
        $request->validate([
            'phone' => 'required|numeric|min:10'
        ]);

        // find or create user model

        $user = User::firstOrCreate([
            'phone' => $request->phone
        ]);

        if (!$user) {
            return response('Could not process a user with this phone number', 401);
        }

        // send one time use code
        $user->notify(new LoginNeedsVerfication());

        // response

        return response('Text message notification send');
    }


    public function verify(Request $request)
    {
        // validate request
        $request->validate([
            'phone' => 'required|numeric|min:10',
            'login_code' => 'required|numeric|between:111111,999999'
        ]);

        // find the user

        $user = User::where('phone', $request->phone)
            ->where('login_code', $request->login_code)
            ->first();

        // if not return message
        if (!$user) {
            return response('Invalid code');
        };

        // code provided the same one saved?
        // return back with auth token

        $user->update([
            'login_code' => null
        ]);

        return $user->createToken($request->login_code)->plainTextToken;
    }
}
