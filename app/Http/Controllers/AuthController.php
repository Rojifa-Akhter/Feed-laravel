<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'first_name'           => 'required|string|max:255',
            'last_name'            => 'required|string|max:255',
            'email'                => 'required|string|email|unique:users,email',
            'password'             => 'required|string|min:6',
            'image'                => 'nullable|image',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 400);
        }

        $new_name = null;
        if ($request->has('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $new_name  = time() . '.' . $extension;
            $path      = $image->move(public_path('uploads/profile_images'), $new_name);
        }


        $user = User::create([
            'first_name'           => $request->first_name,
            'last_name'            => $request->last_name,
            'email'                => $request->email,
            'password'             => Hash::make($request->password),
            'image'                => $new_name,
        ]);


        $token = JWTAuth::fromUser($user);
        return response()->json([
            'status'       => true,
            'message'      => 'Registration Successfully!...',
            'access_token' => $token,
        ], 200);
    }
    public function guard()
    {
        return Auth::guard('api');
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Email or password is incorrect.'], 403);
        }

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['status' => false, 'message' => 'Email or password is incorrect.'], 401);
        }

        return response()->json([
            'status'           => true,
            'message'          => 'Login Successfully',
            'access_token'     => $token,
            'token_type'       => 'bearer',
            'user_information' => $user,
        ], 200);

    }
}
