<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            "name" => "required|max:255",
            "email" => "required|email|unique:users",
            "password" => "required|min:6|confirmed",
            "role" => "in:super_admin,admin,agent,owner",
            "rera_number" =>
                "required_if:role,agent|unique:users,rera_number|max:255", // Required if role is 'agent' and must be unique
                "phone" => 'required|regex:/^\+?[0-9]{1,4}?[0-9]{6,14}$/',
            // 'company_id'=>'required_if:role,owner',
        ]);

        $user = User::create([
            "name" => $fields["name"],
            "email" => $fields["email"],
            "password" => $fields["password"],
            "role" => $fields["role"],
            "phone" => $fields["phone"],
            "rera_number" => $fields["rera_number"] ?? null,
        ]);

        // Generate token
        $token = $user->createToken($request->name)->plainTextToken;

        return response()->json([
            "user" => $user,
            "token" => $token,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            "email" => "required|email|exists:users",
            "password" => "required|min:6",
        ]);

        $user = User::where("email", $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                "message" => "Invalid credentials",
            ];
        }

        $token = $user->createToken($user->name)->plainTextToken;

        return [
            "user" => $user,
            "token" => $token,
        ];
    }

    public function logout(Request $request)
    {
        $request
            ->user()
            ->tokens()
            ->delete();

        return [
            "message" => "Logged out",
        ];
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json($user);
    }
}