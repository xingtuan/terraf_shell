<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFundingCampaignRequest;
use App\Http\Resources\FundingCampaignResource;
use App\Models\Post;
use App\Services\FundingCampaignService;
use Illuminate\Http\JsonResponse;

class FundingCampaignController extends Controller
{
    public function show(Post $post, FundingCampaignService $fundingCampaignService): JsonResponse
    {
        $campaign = $fundingCampaignService->showForAdmin($post);

        return $this->successResponse(
            $campaign !== null ? new FundingCampaignResource($campaign) : null
        );
    }

    public function update(
        UpdateFundingCampaignRequest $request,
        Post $post,
        FundingCampaignService $fundingCampaignService
    ): JsonResponse {
        $campaign = $fundingCampaignService->upsertForPost(
            $post,
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            new FundingCampaignResource($campaign),
            'Funding campaign updated successfully.'
        );
    }

    public function destroy(Post $post, FundingCampaignService $fundingCampaignService): JsonResponse
    {
        $fundingCampaignService->deleteForPost($post, request()->user());

        return $this->successResponse(null, 'Funding campaign deleted successfully.');
    }
}
