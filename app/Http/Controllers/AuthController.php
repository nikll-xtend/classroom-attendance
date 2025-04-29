<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($validated)) {
                Log::warning('Failed login attempt', ['email' => $validated['email']]);

                return response()->json([
                    'message' => 'Invalid login credentials.'
                ], 401);
            }

            $user = Auth::user();

            $abilities = match ($user->role) {
                'admin' => [ 'view-attendance','mark-attendance', 'manage-attendance'],
                'teacher' => ['view-attendance', 'mark-attendance'],
                default => ['view-attendance'],
            };

            $token = $user->createToken('api-token', $abilities)->plainTextToken;

            Log::info('User logged in successfully', ['user_id' => $user->id]);

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Something went wrong during login.'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            Log::info('User logged out successfully', ['user_id' => $request->user()->id]);

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Something went wrong during logout.'
            ], 500);
        }
    }
}
