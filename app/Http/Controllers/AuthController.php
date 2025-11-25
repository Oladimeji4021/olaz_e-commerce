<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UpdateAvatarRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = $data['role'] ?? 'customer';

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // hashed by model cast
            'role'     => $role,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $user = User::where('email', $credentials['email'])->first();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

   public function allUsers(Request $request)
{
    if (!$request->user() || $request->user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $users = User::select('id', 'name', 'email', 'role', 'created_at')
                 ->paginate(50); // 50 per page

    return response()->json([
        'users' => $users->items(),
        'total' => $users->total()
    ]);
}


    public function me(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        return response()->json([
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'address'   => $user->address,
                'avatar'    => $user->avatar,
                'dob'       => $user->dob,
                'joined_at' => $user->created_at,
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

public function updateProfile(UpdateProfileRequest $request)
{
    $user = $request->user();

    $data = $request->only(['name', 'phone', 'address', 'dob']);

    $user->update($data);

    return response()->json([
        'message' => 'Profile updated successfully',
        'user' => $user,
    ]);
}




public function updateAvatar(UpdateAvatarRequest $request)
{
    $user = $request->user();

    // Delete old avatar if it exists
    if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
        Storage::disk('public')->delete('avatars/' . $user->avatar);
    }

    // Store new avatar
    $file = $request->file('avatar');
    $filename = time() . '_' . $file->getClientOriginalName();
    $file->storeAs('avatars', $filename, 'public');

    // Update user's avatar
    $user->update(['avatar' => $filename]);

    return response()->json([
        'message' => 'Avatar updated successfully',
        'avatar' => asset('storage/avatars/' . $filename),
    ]);
}


}