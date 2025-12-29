<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use App\Models\User;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|min:4',
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Check if user needs onboarding (cycle profile setup)
            $needsOnboarding = !$user->cycleProfile()->exists();

            return response()->json([
                'response_code' => 201,
                'status'        => 'success',
                'message'       => 'Successfully registered',
                'token'         => $token,
                'needs_onboarding' => $needsOnboarding,
                'user_info'     => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Registration failed',
            ], 500);
        }
    }

    /**
     * Login with email & password
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'response_code' => 401,
                    'status'        => 'error',
                    'message'       => 'Unauthorized',
                ], 401);
            }

            $user = Auth::user();
            
            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Check if user needs onboarding
            $needsOnboarding = !$user->cycleProfile()->exists();

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Login successful',
                'user_info'     => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'token'       => $token,
                'token_type'  => 'Bearer',
                'needs_onboarding' => $needsOnboarding,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Login failed',
            ], 500);
        }
    }

    /**
     * Google OAuth - Mobile Flow
     * Verify ID token dari Flutter, lalu return Sanctum token
     */
    public function googleAuthMobile(Request $request)
    {
        try {
            $validated = $request->validate([
                'access_token' => 'required|string', // Sebenarnya ini ID token
            ]);

            // Verify ID token dengan Google API
            $idToken = $validated['access_token'];
            $googleUser = $this->verifyGoogleIdToken($idToken);

            if (!$googleUser) {
                Log::error('Invalid Google token', [
                    'token_length' => strlen($idToken),
                    'token_prefix' => substr($idToken, 0, 20),
                ]);

                return response()->json([
                    'response_code' => 401,
                    'status'        => 'error',
                    'message'       => 'Invalid Google token',
                ], 401);
            }

            // Find or create user
            $user = $this->findOrCreateUser($googleUser);

            // Generate Sanctum token
            $token = $user->createToken('google_auth_token')->plainTextToken;

            // Check if user needs onboarding
            $needsOnboarding = !$user->cycleProfile()->exists();

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Login with Google successful',
                'token'         => $token,
                'token_type'    => 'Bearer',
                'needs_onboarding' => $needsOnboarding,
                'user_info'     => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'avatar' => $user->avatar,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Google OAuth Mobile Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Google authentication failed',
                'debug'         => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Verify Google ID Token dengan Google API
     */
    private function verifyGoogleIdToken($idToken)
    {
        try {
            $clientId = config('services.google.client_id');
            
            Log::info('Verifying Google ID token', [
                'client_id' => $clientId,
                'token_length' => strlen($idToken),
            ]);

            // Method 1: Verify dengan Google tokeninfo endpoint
            $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

            if ($response->failed()) {
                Log::error('Google token verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $payload = $response->json();

            Log::info('Google token verification response', [
                'payload' => $payload,
            ]);

            // Verify audience (client ID)
            // ID token bisa punya audience dari Web Client ID atau Android Client ID
            // Kita terima keduanya
            if (!isset($payload['aud'])) {
                Log::error('No audience in Google token payload');
                return null;
            }

            // Check if token is valid (has email)
            if (!isset($payload['email'])) {
                Log::error('No email in Google token payload');
                return null;
            }

            // Check if token expired
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Log::error('Google token expired');
                return null;
            }

            // Return user data
            return (object) [
                'id' => $payload['sub'],
                'email' => $payload['email'],
                'name' => $payload['name'] ?? $payload['email'],
                'avatar' => $payload['picture'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Error verifying Google ID token: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Helper: Find or create user from Google data
     */
    private function findOrCreateUser($googleUser)
    {
        // Cari user berdasarkan Google ID atau email
        $user = User::where('google_id', $googleUser->id)
                   ->orWhere('email', $googleUser->email)
                   ->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name'              => $googleUser->name,
                'email'             => $googleUser->email,
                'google_id'         => $googleUser->id,
                'avatar'            => $googleUser->avatar,
                'password'          => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            Log::info('New user created via Google OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } else {
            // Update existing user
            $updateData = [];
            
            if (!$user->google_id) {
                $updateData['google_id'] = $googleUser->id;
            }
            
            if (!$user->avatar || $user->avatar !== $googleUser->avatar) {
                $updateData['avatar'] = $googleUser->avatar;
            }
            
            if (!$user->email_verified_at) {
                $updateData['email_verified_at'] = now();
            }

            if (!empty($updateData)) {
                $user->update($updateData);
                
                Log::info('Existing user updated via Google OAuth', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'updated_fields' => array_keys($updateData),
                ]);
            }
        }

        return $user;
    }
    /**
     * Get authenticated user info
     */
    public function userInfo(Request $request)
    {
        try {
            $user = $request->user(); // Sanctum automatically gets authenticated user

            if (!$user) {
                return response()->json([
                    'response_code' => 401,
                    'status'        => 'error',
                    'message'       => 'User not authenticated',
                ], 401);
            }

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Fetched user info successfully',
                'data'          => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'avatar'     => $user->avatar,
                    'google_id'  => $user->google_id,
                    'created_at' => $user->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('User Info Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to fetch user info',
            ], 500);
        }
    }

    /**
     * Logout user (revoke Sanctum token)
     */
    public function logOut(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                // Delete all tokens (logout dari semua device)
                $user->tokens()->delete();
                
                // Atau delete hanya current token (logout dari device ini aja):
                // $request->user()->currentAccessToken()->delete();

                Log::info('User logged out', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return response()->json([
                    'response_code' => 200,
                    'status'        => 'success',
                    'message'       => 'Successfully logged out',
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status'        => 'error',
                'message'       => 'User not authenticated',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'An error occurred during logout',
            ], 500);
        }
    }
}