<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tag\CreateRequest;
use App\Http\Requests\Api\Tag\UpdateRequest;
use App\Services\TagService;
use App\Transformers\TagTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tag;

class TagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService,
        private readonly TagTransformer $transformer = new TagTransformer()
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/tags",
     *     summary="List all tags",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of tags.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="mobile"),
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="key", type="string", example="welcome"),
     *                             @OA\Property(
     *                                 property="translations",
     *                                 type="object",
     *                                 @OA\Property(property="en", type="string", example="Welcome"),
     *                                 @OA\Property(property="es", type="string", example="Bienvenido")
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving tags.")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $tags = $this->tagService->getAll();
            
            return $this->successResponse($this->transformer->transformCollection($tags));
        } catch (\Exception $e) {
            Log::error('Error retrieving tags', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                'An error occurred while retrieving tags.',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tags",
     *     summary="Create a new tag",
     *     tags={"Tags"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="mobile")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tag created successfully."),
     *     @OA\Response(response=422, description="Validation error.")
     * )
     */
    public function store(CreateRequest $request): JsonResponse
    {
        $tag = $this->tagService->create($request->validated());
        return $this->successResponse($this->transformer->transform($tag));
    }

    /**
     * @OA\Get(
     *     path="/api/tags/{id}",
     *     summary="Get a tag by id",
     *     tags={"Tags"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Tag id", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tag details."),
     *     @OA\Response(response=404, description="Tag not found.")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tag = $this->tagService->findOrFail($id);
            return $this->successResponse($this->transformer->transform($tag));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Tag not found (id: {$id})", (string) 404);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while retrieving the tag.", (string) 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tags/{id}",
     *     summary="Update a tag",
     *     tags={"Tags"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Tag id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="desktop")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tag updated successfully."),
     *     @OA\Response(response=404, description="Tag not found."),
     *     @OA\Response(response=422, description="Validation error.")
     * )
     */
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        try {
            $tag = $this->tagService->update($id, $request->validated());
            return $this->successResponse($this->transformer->transform($tag));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Tag not found (id: {$id})", (string) 404);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while updating the tag.", (string) 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tags/{id}",
     *     summary="Delete a tag",
     *     tags={"Tags"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Tag id", @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Tag deleted successfully."),
     *     @OA\Response(response=404, description="Tag not found.")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->tagService->delete($id);
            return $this->successResponse(null, (string) 204);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Tag not found (id: {$id})", (string) 404);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while deleting the tag.", (string) 500);
        }
    }
}