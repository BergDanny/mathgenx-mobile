<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class AuthProjectController extends Controller
{
    /**
     * Register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

    $user->assignRole($request->role ?? 'learner');

    $token = $user->createToken('api_token')->plainTextToken;
    $roles = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data'    => [
                'user'  => $user,
                'token' => $token,
                'roles' => $roles,
            ]
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api_token')->plainTextToken;
        $roles = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'  => $user,
                'roles' => $roles,
                'token' => $token,
            ]
        ]);
    }

    /**
     * Profile
     */
    public function profile(Request $request)
    {
    $user = $request->user();
    $roles = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => $user,
                'roles' => $roles,
            ]
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
    }
}
