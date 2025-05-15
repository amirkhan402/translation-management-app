<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Translation\CreateRequest;
use App\Http\Requests\Api\Translation\UpdateRequest;
use App\Services\TranslationService;
use App\Transformers\TranslationTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Contracts\Services\TranslationServiceInterface;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Translations",
 *     description="API Endpoints for managing translations"
 * )
 */

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly TranslationTransformer $transformer = new TranslationTransformer()
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/translations",
     *     summary="Get a paginated list of translations with optional search and filter capabilities",
     *     description="Retrieve translations with support for searching by key/value, filtering by locale/tag, and pagination. All search and filter parameters are optional.",
     *     tags={"Translations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="query",
     *         description="Search translations by key (case-insensitive partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="welcome")
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="query",
     *         description="Search translations by content (case-insensitive partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="Welcome to our app")
     *     ),
     *     @OA\Parameter(
     *         name="tag",
     *         in="query",
     *         description="Filter translations by tag name (exact match)",
     *         required=false,
     *         @OA\Schema(type="string", example="common")
     *     ),
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         description="Filter translations by locale code (exact match)",
     *         required=false,
     *         @OA\Schema(type="string", example="en", pattern="^[a-z]{2}(_[A-Z]{2})?$")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (default: 15, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination (default: 1)",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved translations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"id", "key", "locale", "value"},
     *                         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                         @OA\Property(property="key", type="string", example="welcome.message"),
     *                         @OA\Property(property="locale", type="string", example="en_US"),
     *                         @OA\Property(property="value", type="string", example="Welcome to our application"),
     *                         @OA\Property(
     *                             property="tags",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 required={"id", "name"},
     *                                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002"),
     *                                 @OA\Property(property="name", type="string", example="common")
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
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=5),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="to", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=75)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving translations.")
     *         )
     *     ),
     *     @OA\Examples(
     *         example="Basic listing",
     *         summary="Get all translations (paginated)",
     *         value={"GET": "/api/translations?per_page=20"}
     *     ),
     *     @OA\Examples(
     *         example="Search by key",
     *         summary="Search translations by key",
     *         value={"GET": "/api/translations?key=welcome&per_page=20"}
     *     ),
     *     @OA\Examples(
     *         example="Search with multiple filters",
     *         summary="Search translations with multiple filters",
     *         value={"GET": "/api/translations?key=welcome&locale=en_US&tag=common&per_page=20"}
     *     )
     * )
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->query('per_page', TranslationServiceInterface::DEFAULT_PER_PAGE), 100);
            
            // Get search parameters
            $filters = [
                'key' => $request->query('key'),
                'value' => $request->query('value'),
                'tag' => $request->query('tag'),
                'locale' => $request->query('locale')
            ];

            // Remove null filters
            $filters = array_filter($filters, fn($value) => $value !== null);

            $translations = $this->translationService->getAll($perPage, $filters);
            return $this->successResponse($this->transformer->transformPaginated($translations));
        } catch (\Exception $e) {
            Log::error('Error in TranslationController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters ?? []
            ]);
            return $this->errorResponse("An error occurred while retrieving translations.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/translation",
     *     summary="Create a new translation",
     *     tags={"Translations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key", "locale", "value"},
     *             @OA\Property(
     *                 property="key",
     *                 type="string",
     *                 example="welcome.message",
     *                 description="Translation key (lowercase letters, numbers, underscores, and dots only)",
     *                 maxLength=255,
     *                 pattern="^[a-z0-9_\.]+$"
     *             ),
     *             @OA\Property(
     *                 property="locale",
     *                 type="string",
     *                 example="en",
     *                 description="Locale code (e.g., 'en' or 'en_US')",
     *                 maxLength=5,
     *                 pattern="^[a-z]{2}(_[A-Z]{2})?$"
     *             ),
     *             @OA\Property(
     *                 property="value",
     *                 type="string",
     *                 example="Welcome to Translation Management System.",
     *                 description="Translation value",
     *                 maxLength=65535
     *             ),
     *             @OA\Property(
     *                 property="tag_ids",
     *                 type="array",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"550e8400-e29b-41d4-a716-446655440002"},
     *                 description="Array of tag UUIDs (no duplicates allowed)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Translation created successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Translation created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 required={"id", "key", "locale", "value"},
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="key", type="string", example="welcome.message"),
     *                 @OA\Property(property="locale", type="string", example="en_US"),
     *                 @OA\Property(property="value", type="string", example="Welcome to Translation Management System."),
     *                 @OA\Property(
     *                     property="tags",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"id", "name"},
     *                         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002"),
     *                         @OA\Property(property="name", type="string", example="common")
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or duplicate translation",
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
     *                             property="key",
     *                             type="array",
     *                             @OA\Items(type="string", example="The key must contain only lowercase letters, numbers, underscores, and dots.")
     *                         ),
     *                         @OA\Property(
     *                             property="locale",
     *                             type="array",
     *                             @OA\Items(type="string", example="The locale must be in the format 'en' or 'en_US'.")
     *                         ),
     *                         @OA\Property(
     *                             property="value",
     *                             type="array",
     *                             @OA\Items(type="string", example="The value field is required.")
     *                         ),
     *                         @OA\Property(
     *                             property="tag_ids",
     *                             type="array",
     *                             @OA\Items(type="string", example="Duplicate tag IDs are not allowed.")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         example="An error occurred while creating the translation. A translation with key 'welcome.message' and locale 'en_US' already exists."
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An error occurred while creating the translation."
     *             )
     *         )
     *     )
     * )
     */
    public function store(CreateRequest $request): JsonResponse
    {
        try {
            $translation = $this->translationService->create($request->validated());
            $this->translationService->clearExportCache();
            return $this->successResponse(
                $this->transformer->transform($translation->fresh(['translationKey.tags'])),
                'Translation created successfully',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            Log::error('Error in TranslationController::store', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'input' => $request->validated()
            ]);
            return $this->errorResponse("An error occurred while creating the translation. " . $e->getMessage(), null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/translation/{id}",
     *     summary="Get a translation by id",
     *     tags={"Translations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Translation UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation details.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 required={"id", "key", "locale", "value"},
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="key", type="string", example="welcome"),
     *                 @OA\Property(property="locale", type="string", example="en"),
     *                 @OA\Property(property="value", type="string", example="Welcome to our application"),
     *                 @OA\Property(
     *                     property="tags",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"id", "name"},
     *                         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002"),
     *                         @OA\Property(property="name", type="string", example="common")
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Translation not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No translation found with ID: 4846c2ea-5bbc-4d13-8d2e-055ed03effc3")
     *                 )
     *             )
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
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving the translation"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="error",
     *                     type="array",
     *                     @OA\Items(type="string", example="Internal server error message")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                return $this->errorResponse(
                    "Invalid UUID format",
                    ['id' => ['The provided ID is not a valid UUID.']],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $translation = $this->translationService->findOrFail($id);
            return $this->successResponse($this->transformer->transform($translation));
        } catch (ModelNotFoundException $e) {
            Log::info('Translation not found', ['id' => $id]);
            return $this->errorResponse(
                "Translation not found",
                ['id' => ["No translation found with ID: {$id}"]],
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            Log::error('Error in TranslationController::show', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            return $this->errorResponse(
                "An error occurred while retrieving the translation",
                ['error' => [$e->getMessage()]],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/translation/{id}",
     *     summary="Update a translation",
     *     tags={"Translations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Translation UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="key",
     *                 type="string",
     *                 example="welcome.message",
     *                 description="Translation key (lowercase letters, numbers, underscores, and dots only)",
     *                 maxLength=255,
     *                 pattern="^[a-z0-9_\.]+$"
     *             ),
     *             @OA\Property(
     *                 property="locale",
     *                 type="string",
     *                 example="en",
     *                 description="Locale code (e.g., 'en' or 'en_US')",
     *                 maxLength=5,
     *                 pattern="^[a-z]{2}(_[A-Z]{2})?$"
     *             ),
     *             @OA\Property(
     *                 property="value",
     *                 type="string",
     *                 example="Updated welcome message.",
     *                 description="Translation value",
     *                 maxLength=65535
     *             ),
     *             @OA\Property(
     *                 property="tag_ids",
     *                 type="array",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"550e8400-e29b-41d4-a716-446655440002"},
     *                 description="Array of tag UUIDs (no duplicates allowed)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation updated successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 required={"id", "key", "locale", "value"},
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="key", type="string", example="welcome.message"),
     *                 @OA\Property(property="locale", type="string", example="en_US"),
     *                 @OA\Property(property="value", type="string", example="Updated welcome message."),
     *                 @OA\Property(
     *                     property="tags",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"id", "name"},
     *                         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002"),
     *                         @OA\Property(property="name", type="string", example="common")
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Translation not found (id: 4846c2ea-5bbc-4d13-8d2e-055ed03effcc)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or duplicate translation",
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
     *                             property="tag_ids.0",
     *                             type="array",
     *                             @OA\Items(type="string", example="The selected tag_ids.0 is invalid.")
     *                         ),
     *                         @OA\Property(
     *                             property="key",
     *                             type="array",
     *                             @OA\Items(type="string", example="The key must contain only lowercase letters, numbers, underscores, and dots.")
     *                         ),
     *                         @OA\Property(
     *                             property="locale",
     *                             type="array",
     *                             @OA\Items(type="string", example="The locale must be in the format 'en' or 'en_US'.")
     *                         ),
     *                         @OA\Property(
     *                             property="tag_ids",
     *                             type="array",
     *                             @OA\Items(type="string", example="Duplicate tag IDs are not allowed.")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         example="An error occurred while updating the translation: A translation with key 'welcome.message_1' and locale 'en_US' already exists."
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An error occurred while updating the translation."
     *             )
     *         )
     *     )
     * )
     */
    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        try {
            Log::info('Updating translation', [
                'id' => $id,
                'data' => $request->validated()
            ]);

            $translation = $this->translationService->update($id, $request->validated());
            $this->translationService->clearExportCache();
            return $this->successResponse($this->transformer->transform($translation));
        } catch (ModelNotFoundException $e) {
            Log::warning('Translation not found during update', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse("Translation not found (id: {$id})", null, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Error in TranslationController::update', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
                'input' => $request->all()
            ]);
            return $this->errorResponse(
                "An error occurred while updating the translation: " . $e->getMessage(),
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/translation/{id}",
     *     summary="Delete a translation",
     *     tags={"Translations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Translation UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Translation deleted successfully")
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
     *         description="Translation not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Translation not found (id: 4846c2ea-5bbc-4d13-8d2e-055ed03effcc)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the translation.")
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

            $this->translationService->delete($id);
            $this->translationService->clearExportCache();
            return $this->successResponse(null, 'Translation deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Translation not found (id: {$id})", null, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while deleting the translation.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/translations/export",
     *     summary="Export translations for frontend use (grouped by key, with locale=>value pairs and tags)",
     *     tags={"Translations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A JSON export of translations suitable for frontend applications",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"key", "translations", "tags"},
     *                     @OA\Property(
     *                         property="key",
     *                         type="string",
     *                         example="auth.button.00ro",
     *                         description="The translation key"
     *                     ),
     *                     @OA\Property(
     *                         property="translations",
     *                         type="object",
     *                         description="Object containing locale=>value pairs",
     *                         @OA\Property(property="en", type="string", example="auth.button.00ro in en"),
     *                         @OA\Property(property="de", type="string", example="auth.button.00ro in de"),
     *                         @OA\Property(property="es", type="string", example="auth.button.00ro in es"),
     *                         @OA\Property(property="fr", type="string", example="auth.button.00ro in fr"),
     *                         @OA\Property(property="it", type="string", example="auth.button.00ro in it"),
     *                         @OA\Property(property="ja", type="string", example="auth.button.00ro in ja"),
     *                         @OA\Property(property="nl", type="string", example="auth.button.00ro in nl"),
     *                         @OA\Property(property="pt", type="string", example="auth.button.00ro in pt"),
     *                         @OA\Property(property="ru", type="string", example="auth.button.00ro in ru"),
     *                         @OA\Property(property="zh", type="string", example="auth.button.00ro in zh")
     *                     ),
     *                     @OA\Property(
     *                         property="tags",
     *                         type="array",
     *                         description="Array of tag names associated with this translation key",
     *                         @OA\Items(type="string", example="email")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while exporting translations")
     *         )
     *     )
     * )
     */
    public function export(): JsonResponse
    {
        try {
            Log::info('Starting export request in TranslationController');
            
            $exportData = $this->translationService->export();
            
            Log::info('Export data prepared', [
                'total_keys' => count($exportData),
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
            ]);
            
            return $this->successResponse($exportData);
        } catch (\Exception $e) {
            Log::error('Error in TranslationController::export', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                "An error occurred while exporting translations: " . $e->getMessage(),
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get all translations with full relations.
     * This method is prepared for future use and is not currently exposed as an API endpoint.
     */
    public function indexWithRelations(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->query('per_page', TranslationServiceInterface::DEFAULT_PER_PAGE), 100);
            $translations = $this->translationService->getAllWithRelations($perPage);
            return $this->successResponse($this->transformer->transformPaginatedWithRelations($translations));
        } catch (\Exception $e) {
            Log::error('Error in TranslationController::indexWithRelations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse("An error occurred while retrieving translations with relations.", 500);
        }
    }
}
