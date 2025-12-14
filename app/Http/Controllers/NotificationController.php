<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Register device token
     * POST: /api/notification/register-device
     * 
     * Body:
     * {
     *   "token": "fcm_device_token_here"
     * }
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'token' => 'required|string|min:10',
        ]);

        $user = $request->user();

        try {
            DeviceToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token' => $request->token,
                ],
                []
            );

            Log::info('Device token registered', [
                'user_id' => $user->id,
                'token' => substr($request->token, 0, 20) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to register device token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register device',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unregister device token
     * DELETE: /api/notification/unregister-device
     * 
     * Body:
     * {
     *   "token": "fcm_device_token_here"
     * }
     */
    public function unregisterDevice(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $deleted = DeviceToken::where('user_id', $user->id)
                ->where('token', $request->token)
                ->delete();

            if ($deleted) {
                Log::info('Device token unregistered', [
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Device unregistered successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Device token not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to unregister device', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unregister device',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all registered devices
     * GET: /api/notification/devices
     */
    public function getDevices(Request $request)
    {
        $user = $request->user();

        $devices = $user->deviceTokens()
            ->select('id', 'token', 'created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
            'total' => $devices->count(),
        ]);
    }
}
