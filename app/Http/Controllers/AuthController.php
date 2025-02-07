<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use App\Models\User;

class AuthController extends Controller
{

    public function signUp(Request $request)
    {
        // Validate input data
        $validatedData = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'password'  => 'required|string|min:6',
            'mobile_no' => 'nullable|string',
        ]);

        // Extract input values
        $name     = $validatedData['name'];
        $email    = $validatedData['email'];
        $password = $validatedData['password'];
        $mobileNo = $validatedData['mobile_no'] ?? null;

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            return response()->json(['error' => 'Email already exists'], 400);
        }

        try {
            // Generate a new session token (for example, using a UUID)
            $sessionToken = (string) Str::uuid();

            // Create a new user record in the database
            $user = User::create([
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make($password),
                'mobile_no'  => $mobileNo,
                'session_id' => $sessionToken,
            ]);

            // Log the successful creation
            Log::info('User creation successful:', [
                'message' => 'User created successfully',
                'session' => $user->session_id,
                'userId'  => $user->id,
                'email'   => $user->email,
                'name'    => $user->name,
            ]);

            // Return JSON response with the new user details
            return response()->json([
                'message' => 'User created successfully',
                'session' => $user->session_id,
                'id'      => $user->id,
                'email'   => $user->email,
                'name'    => $user->name,
            ], 201);
        } catch (\Exception $e) {
            // Log and return an error response if something goes wrong
            Log::error('Error during sign-up:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }


    public function loginUser(Request $request)
    {
        // Validate the input
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required',
            'forceLogout' => 'nullable|boolean',
        ]);

        $email       = $request->input('email');
        $password    = $request->input('password');
        $forceLogout = $request->input('forceLogout', false);

        try {
            // 1. Check if the email exists in the database
            $user = DB::table('users')
                ->select('id', 'name', 'email', 'password', 'session_id')
                ->where('email', $email)
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            $storedHash = $user->password;

            // 2. Normalize $2y$ to $2b$ if needed
            if (Str::startsWith($storedHash, '$2b$')) {
                $storedHash = '$2y$' . substr($storedHash, 4);
            }

            // 3. Compare provided password with the stored password
            if (!Hash::check($password, $storedHash)) {
                return response()->json([
                    'message' => 'Invalid password.'
                ], 401);
            }

            // 4. Check if the user is already logged in (session_id is not null)
            if ($user->session_id && !$forceLogout) {
                return response()->json([
                    'message' => 'You are already logged in on another device.',
                    'session' => $user->session_id,
                    'prompt'  => 'Do you want to log out from other devices? (yes or no)'
                ], 200);
            }

            // 5. Handle force logout: clear session_id if forceLogout is true
            if ($forceLogout) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['session_id' => null]);
            }

            // 6. Create a new session (JWT token)
            $payload = [
                'email' => $user->email,
                'exp'   => time() + 3600 // token valid for 1 hour
            ];
            $jwtSecret  = env('JWT_SECRET'); // Ensure you have JWT_SECRET in your .env file
            $newSession = JWT::encode($payload, $jwtSecret, 'HS256');

            // 7. Update the database with the new session
            DB::table('users')
                ->where('email', $user->email)
                ->update(['session_id' => $newSession]);

            // 8. Return the new session token along with user details
            return response()->json([
                'message'  => 'Login successful',
                'session'  => $newSession,
                'userId'   => (string)$user->id,
                'userName' => $user->name,
                'email'    => $user->email,
            ], 200);
        } catch (\Exception $e) {
            // Handle errors during login
            return response()->json([
                'message' => 'An error occurred during login.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function logOut(Request $request)
    {
        // Validate request
        $request->validate([
            'userId' => 'required|exists:users,id',
        ]);

        try {
            // Find user by ID
            $user = User::find($request->userId);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Update session_id to NULL
            $user->session_id = null;
            $user->save();

            Log::info('User logged out successfully', ['userId' => $user->id]);

            return response()->json(['message' => 'Logout successful'], 200);
        } catch (\Exception $e) {
            Log::error('Error during logout', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error logging out'], 500);
        }
    }

    public function logOutByEmail(Request $request)
    {
        // Validate request
        $request->validate([
            'emailId'  => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        try {
            // Find user by email
            $user = User::where('email', $request->emailId)->first();

            // Check if user exists
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Update session_id to NULL
            $user->session_id = null;
            $user->save();

            Log::info('User logged out successfully', ['email' => $user->email]);

            return response()->json(['message' => 'Logout successful'], 200);
        } catch (\Exception $e) {
            Log::error('Error during logout', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error logging out'], 500);
        }
    }

    public function checkUserSession(Request $request)
    {
        // Validate request
        $request->validate([
            'userId'  => 'required|exists:users,id',
            'session' => 'required|string',
        ]);

        try {
            // Find user by ID
            $user = User::find($request->userId);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 200);
            }

            // Case 3: Session ID is NULL
            if (!$user->session_id) {
                return response()->json(['success' => false, 'message' => 'Session is not active.'], 200);
            }

            // Case 4: Session ID does not match
            if ($user->session_id !== $request->session) {
                return response()->json(['success' => false, 'message' => 'Session does not match.'], 200);
            }

            // Case 5: Session is valid
            return response()->json(['success' => true, 'message' => 'Session is active.'], 200);
        } catch (\Exception $e) {
            Log::error('Error checking user session:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server error.'], 500);
        }
    }
}
