<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tag\CreateRequest;
use App\Http\Requests\Api\Tag\UpdateRequest;
use App\Contracts\Services\TagServiceInterface;
use App\Transformers\TagTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tag;

/**
 * @OA\Tag(
 *     name="Tags",
 *     description="API Endpoints for managing translation tags"
 * )
 */
class TagController extends Controller
{
    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagTransformer $transformer
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/tags",
     *     summary="List all tags with optional name filter",
     *     description="Retrieve a paginated list of tags with their translation keys. Supports filtering by name and pagination.",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter tags by name (case-insensitive partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="mobile")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A paginated list of tags.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="success",
     *                     type="boolean",
     *                     example=true
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                         @OA\Property(property="name", type="string", example="mobile"),
     *                         @OA\Property(
     *                             property="translation_keys",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
     *                                 @OA\Property(property="key", type="string", example="welcome")
     *                             )
     *                         ),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=10),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=150)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving tags.")
     *         )
     *     ),
     *     @OA\Examples(
     *         example="Basic listing",
     *         summary="Get all tags (paginated)",
     *         value={"GET": "/api/tags?per_page=20"}
     *     ),
     *     @OA\Examples(
     *         example="Search by name",
     *         summary="Search tags by name",
     *         value={"GET": "/api/tags?name=mobile&per_page=20"}
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $perPage = min((int) request()->input('per_page', TagServiceInterface::DEFAULT_PER_PAGE), 100);
            
            // Get filter parameters
            $filters = [
                'name' => request()->query('name')
            ];

            // Remove null filters
            $filters = array_filter($filters, fn($value) => $value !== null);

            $tags = $this->tagService->getAll($perPage, $filters);
            return $this->successResponse($this->transformer->transformPaginated($tags));
        } catch (\Exception $e) {
            Log::error('Error retrieving tags', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters ?? []
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
     *     path="/api/tag",
     *     summary="Create a new tag",
     *     description="Create a new tag with the provided name. The name must be unique.",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="mobile",
     *                 description="The name of the tag. Must be unique and not exceed 255 characters."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag created successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="data",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="name", type="string", example="mobile"),
     *                     @OA\Property(property="translation_keys", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
     *                 ),
     *                 @OA\Property(property="message", type="string", example="Tag created successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or duplicate tag name",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="The given data was invalid."),
     *                     @OA\Property(
     *                         property="errors",
     *                         type="object",
     *                         @OA\Property(
     *                             property="name",
     *                             type="array",
     *                             @OA\Items(type="string", example="The name field is required.")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="A tag with name 'tablet' already exists."),
     *                     @OA\Property(
     *                         property="errors",
     *                         type="object",
     *                         @OA\Property(
     *                             property="name",
     *                             type="array",
     *                             @OA\Items(type="string", example="A tag with this name already exists.")
     *                         )
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the tag.")
     *         )
     *     )
     * )
     */
    public function store(CreateRequest $request): JsonResponse
    {
        try {
            $tag = $this->tagService->create($request->validated());
            return $this->successResponse(
                $this->transformer->transform($tag),
                'Tag created successfully',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            Log::error('Error creating tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated()
            ]);
            
            // Check if this is a duplicate tag name error
            if (str_contains($e->getMessage(), "A tag with name")) {
                return $this->errorResponse(
                    $e->getMessage(),
                    ['name' => ['A tag with this name already exists.']],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            return $this->errorResponse(
                'An error occurred while creating the tag.',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tag/{id}",
     *     summary="Get a tag by id",
     *     description="Retrieve a specific tag by its UUID. By default, only translation keys are included without their translations for better performance.",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Tag UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag details retrieved successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="data",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="name", type="string", example="mobile"),
     *                     @OA\Property(
     *                         property="translation_keys",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
     *                             @OA\Property(property="key", type="string", example="welcome")
     *                         )
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tag not found (id: 550e8400-e29b-41d4-a716-446655440000)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving the tag.")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $tag = $this->tagService->findOrFail($id);
            return $this->successResponse($this->transformer->transform($tag));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                "Tag not found (id: {$id})",
                null,
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            
            return $this->errorResponse(
                'An error occurred while retrieving the tag.',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tag/{id}",
     *     summary="Update a tag",
     *     description="Update an existing tag's name. The name must be unique.",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Tag UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="desktop",
     *                 description="The new name for the tag. Must be unique and not exceed 255 characters."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag updated successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="data",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="name", type="string", example="desktop"),
     *                     @OA\Property(property="translation_keys", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
     *                 ),
     *                 @OA\Property(property="message", type="string", example="Tag updated successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tag not found (id: 550e8400-e29b-41d4-a716-446655440000)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while updating the tag.")
     *         )
     *     )
     * )
     */
    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        try {
            $tag = $this->tagService->update($id, $request->validated());
            return $this->successResponse(
                $this->transformer->transform($tag),
                'Tag updated successfully'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                "Tag not found (id: {$id})",
                null,
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            Log::error('Error updating tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
                'data' => $request->validated()
            ]);
            
            return $this->errorResponse(
                'An error occurred while updating the tag.',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tag/{id}",
     *     summary="Delete a tag",
     *     description="Delete a tag by its UUID. This will also remove all associations with translation keys.",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Tag UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tag deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid UUID format",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid UUID format"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The provided ID is not a valid UUID.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tag not found (id: 550e8400-e29b-41d4-a716-446655440000)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the tag.")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                return $this->errorResponse(
                    "Invalid UUID format",
                    ['id' => ['The provided ID is not a valid UUID.']],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->tagService->delete($id);
            return $this->successResponse(null, 'Tag deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                "Tag not found (id: {$id})",
                null,
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            Log::error('Error deleting tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            
            return $this->errorResponse(
                'An error occurred while deleting the tag.',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
