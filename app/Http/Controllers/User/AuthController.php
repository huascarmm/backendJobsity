<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use JWTAuth; 
use Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    protected $user;
    public function __construct(){
        $this->user = new User;
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'username'=>'required | string',
            'twitter'=>'required | string',
            'email'=>'required | string',
            'password'=>'required | string | min:6',

        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>$validator->messages()->toArray()
            ], 400);
        }

        $check_email = $this->user->where('email', $request->email)->count();
        if($check_email>0){
            return response()->json([
                'success'=>false,
                'message'=>'Email already registered, login or use another.'
            ], 409);
        }

        $successRegister = $this->user::create([
            'username'=>$request->username,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'twitter'=>$request->twitter
        ]);

        if($successRegister){
            return $this->login($request);
        } else {
            return response()->json([
                'success'=>false,
                'message'=>'We have problems in the server, please try again later'
            ], 500);
        }
    }

    public function login(Request $request){
        $validator = Validator::make($request->only('email', 'password'), [
            'email'=>'required | string',
            'password'=>'required | string | min:8',
        ]);


        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>$validator->messages()->toArray()
            ], 400);
        }

        $jwt_token = null;
        $input = $request->only('email', 'password');
        if(!$jwt_token = auth('users')->attempt($input)){
            return response()->json([
                'success'=>false,
                'message'=>'invalid email or password'
            ], 401);
        }

        $user = auth('users')->authenticate($jwt_token);
        return response()->json([
            'success'=>true,
            'token'=>$jwt_token,
            'id'=>$user->id
        ], 200);
    }

    function user ($id){
        try {
            $user = User::findOrFail($id);
            return response()->json([
            'success'=>false,
            'data'=>$user 
        ], 404);
        } catch (\Throwable $th) {
                    return response()->json([
            'success'=>false,
            'data'=>'Not found'
        ], 404);
        }
    }

    function userPosts ($id){
        try {
            $user = User::findOrFail($id);
            return response()->json([
                'success'=>false,
                'data'=>$user->entries 
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'success'=>false,
                'data'=>'Not found'
            ], 404);
        }
    }

}
