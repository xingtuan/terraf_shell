<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, ProfileService $profileService): JsonResponse
    {
        $user = $profileService->update($request->user(), $request->validated());

        return $this->successResponse(
            new UserResource($user),
            'Profile updated successfully.'
        );
    }
}
