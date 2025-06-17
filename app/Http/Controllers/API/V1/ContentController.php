<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\V1\ApiController;
use App\Http\Requests\CreateContentRequest;
use App\Http\Requests\IndexContentRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Http\Resources\ContentResource;
use App\Models\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Src\Domain\Content\DTOs\CreateContentData;
use Src\Domain\Content\DTOs\UpdateContentData;
use Src\Domain\Content\Services\ContentService;
use Src\Domain\Content\DTOs\ContentFilterData;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class ContentController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(public ContentService $contentService)
    {}

    public function index(IndexContentRequest $request): JsonResponse
    {
        $filterData = ContentFilterData::fromRequest($request);
        $contents = $this->contentService->getContents($filterData);

        return $this->collectionResponse(
            ContentResource::collection($contents),
            'Contents retrieved successfully'
        );	
	
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contents",
     *     summary="Create new content",
     *     tags={"Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreContentRequest")),
     *     @OA\Response(response=201, description="Content created successfully")
     * )
     */
    public function store(CreateContentRequest $request): JsonResponse
    {
        try {
            $this->authorize('create content', Content::class);

            $data = CreateContentData::fromRequest(data: $request->toArray());
            $user = $request->user();

            $content = $this->contentService->createContent(data: $data, user: $user);

            return $this->resourceResponse(
                new ContentResource($content),
                'Content created successfully',
                Response::HTTP_CREATED
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse('Unauthorized', 403);

        } catch (\Exception $e) {
            Log::error('Content creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->toArray(),
                'user_id' => $request->user()?->id,
            ]);

            return $this->errorResponse($e->getMessage(), 422);
        }
        
    }

    /**
     * @OA\Get(
     *     path="/api/v1/contents/{content}",
     *     summary="Get specific content",
     *     tags={"Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="content", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Content retrieved successfully")
     * )
     */
    public function show(string $identifier): JsonResponse
    {
        $content = is_numeric($identifier) 
            ? $this->contentService->findContentById((int) $identifier)
            : $this->contentService->findContentBySlug($identifier);

        if (!$content) {
            return $this->errorResponse('Content not found', 404);
        }

        return $this->resourceResponse(
            new ContentResource($content),
            'Content retrieved successfully'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/contents/{content}",
     *     summary="Update content",
     *     tags={"Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="content", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateContentRequest")),
     *     @OA\Response(response=200, description="Content updated successfully")
     * )
     */
    public function update(UpdateContentRequest $request, int $id): JsonResponse
    {
        try {
            // First find the content to pass to authorize
            $content = $this->contentService->findContentById($id);
            
            if (!$content) {
                return $this->errorResponse('Content not found', 404);
            }
            
            $this->authorize('update', $content);

            $data = UpdateContentData::fromRequest(data: $request->toArray());
            $user = $request->user();

            $content = $this->contentService->updateContent($id, $data, $user);

            return $this->resourceResponse(
                new ContentResource($content),
                'Content updated successfully'
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse('Unauthorized', 403);

        } catch (\Exception $e) {
            Log::error('Content update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data ?? null,
                'user_id' => $user->id ?? null
            ]);

            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/contents/{content}",
     *     summary="Delete content",
     *     tags={"Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="content", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Content deleted successfully")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // First find the content to pass to authorize
            $content = $this->contentService->findContentById($id);
            
            if (!$content) {
                return $this->errorResponse('Content not found', 404);
            }
            
            $this->authorize('delete', $content);

            $deleted = $this->contentService->deleteContent($id);

            if (!$deleted) {
                return $this->errorResponse('Content not found', 404);
            }

            return $this->successResponse(null, 'Content deleted successfully');

        } catch (AuthorizationException $e) {
            return $this->errorResponse('Unauthorized', 403);
            
        } catch (\Exception $e) {
            Log::error('Content deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_id' => $id
            ]);
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contents/{content}/publish",
     *     summary="Publish content",
     *     tags={"Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="content", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Content published successfully")
     * )
     */
    public function publish(int $id): JsonResponse
    {
        try {
            // First find the content to pass to authorize
            $content = $this->contentService->findContentById($id);
            
            if (!$content) {
                return $this->errorResponse('Content not found', 404);
            }
            
            $this->authorize('publish', $content);

            $this->contentService->publishContent($id);
            
            // Get the updated content, bypassing cache
            $updatedContent = Content::with(['author', 'categories', 'tags', 'media'])->find($id);
            
            return $this->resourceResponse(
                new ContentResource($updatedContent),
                'Content published successfully'
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Unauthorized', 403);
            
        } catch (\Exception $e) {
            Log::error('Content publishing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_id' => $id
            ]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contents/{content}/draft",
     *     summary="Draft content",
     *     tags={"Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="content", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Content archived successfully")
     * )
     */
    public function draft(int $id): JsonResponse
    {
        try {
            // First find the content to pass to authorize
            $content = $this->contentService->findContentById($id);
            
            if (!$content) {
                return $this->errorResponse('Content not found', 404);
            }
            
            $this->authorize('draft', $content);

            $this->contentService->draftContent($id);
            
            // Get the updated content, bypassing cache
            $updatedContent = Content::with(['author', 'categories', 'tags', 'media'])->find($id);
            
            return $this->resourceResponse(
                new ContentResource($updatedContent),
                'Content drafted successfully'
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse('Unauthorized', 403);

        } catch (\Exception $e) {
            Log::error('Content drafting failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_id' => $id
            ]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contents/{content}/archive",
     *     summary="Archive content",
     *     tags={"Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="content", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Content archived successfully")
     * )
     */
    public function archive(int $id): JsonResponse
    {
        try {
            $this->authorize('archive', Content::class);

            $content = $this->contentService->findContentById($id);
            
            if (!$content) {
                return $this->errorResponse('Content not found', 404);
            }

            $this->contentService->archiveContent($id);
            
            // Get the updated content, bypassing cache
            $updatedContent = Content::with(['author', 'categories', 'tags', 'media'])->find($id);
            
            return $this->resourceResponse(
                new ContentResource($updatedContent),
                'Content archived successfully'
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse('Unauthorized', 403);

        } catch (\Exception $e) {
            Log::error('Content archiving failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_id' => $id
            ]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get paginated contents by category slug or id
     */
    public function category(string $category, Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $contents = is_numeric($category)
            ? $this->contentService->getContentsByCategoryId((int)$category, $perPage, $page)
            : $this->contentService->getContentsByCategorySlug($category, $perPage, $page);
        return $this->collectionResponse(ContentResource::collection($contents), 'Contents by category retrieved successfully');
    }

    /**
     * Get paginated contents by tag slug or id
     */
    public function tag(string $tag, Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $contents = is_numeric($tag)
            ? $this->contentService->getContentsByTagId((int)$tag, $perPage, $page)
            : $this->contentService->getContentsByTagSlug($tag, $perPage, $page);
        return $this->collectionResponse(ContentResource::collection($contents), 'Contents by tag retrieved successfully');
    }
}
