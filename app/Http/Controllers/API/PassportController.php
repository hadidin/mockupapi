<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;

class PassportController extends Controller
{
    //
    public $successStatus = 200;

    public function login()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            return response()->json(['success' => $success], $this->successStatus);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
//            'user_level' => 'required',
//            'access_level' => 'required',
//            'site_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $input = $request->all();
//        $access_level = $request->access_level;
//        if ($access_level == 61) {
//            //send activation code here
//            $mailParams = array('project' => 'kiplePark', 'template_id' => 'pos-activation-code');
//            $mailParams['recipients'][0] = array('email' => $request->email, 'templateParams' => array('name' => $request->name, 'code' => '12346777'));
//            Mail::send($mailParams);
//        }

        $input['password'] = bcrypt($input['password']);


        $user = User::create($input);
        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['name'] = $user->name;

        return response()->json(['success' => $success], $this->successStatus);
    }

    /**
     * details api
     *
     * @return \Illuminate\Http\Response
     */
    public function getDetails()
    {
        $user = Auth::user();
//        echo "hai";die;
        return response()->json(['success' => $user], $this->successStatus);
    }
    public function getId()
    {
        return Auth::user();
    }
}
