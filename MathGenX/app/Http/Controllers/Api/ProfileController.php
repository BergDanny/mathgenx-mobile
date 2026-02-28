<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends BaseController
{
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
        ]);

        DB::beginTransaction();
        try {
            $user = $request->user();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            DB::commit();
            return $this->sendResponse($user->only(['name', 'email']), 'Profile updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to update profile.', [$e->getMessage()]);
        }
    }
}
