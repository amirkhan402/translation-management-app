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
     *     summary="List all translations",
     *     tags={"Translations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="A list of translations (grouped by key) (or flat if not grouped).")
     * )
     */
    public function index(): JsonResponse
    {
        $translations = $this->translationService->getAll();
        return $this->successResponse($this->transformer->transformCollection($translations));
    }

    /**
     * @OA\Post(
     *     path="/api/translations",
     *     summary="Create a new translation",
     *     tags={"Translations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key", "locale", "value"},
     *             @OA\Property(property="key", type="string", example="welcome"),
     *             @OA\Property(property="locale", type="string", example="en"),
     *             @OA\Property(property="value", type="string", example="Welcome to Translation Management System."),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), example={1,2})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Translation created successfully."),
     *     @OA\Response(response=422, description="Validation error.")
     * )
     */
    public function store(CreateRequest $request): JsonResponse
    {
        $translation = $this->translationService->create($request->validated());
        return $this->successResponse($this->transformer->transform($translation));
    }

    /**
     * @OA\Get(
     *     path="/api/translations/{id}",
     *     summary="Get a translation by id",
     *     tags={"Translations"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Translation id", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Translation details."),
     *     @OA\Response(response=404, description="Translation not found.")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $translation = $this->translationService->findOrFail($id);
            return $this->successResponse($this->transformer->transform($translation));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Translation not found (id: {$id})", (string) 404);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while retrieving the translation.", (string) 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/translations/{id}",
     *     summary="Update a translation",
     *     tags={"Translations"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Translation id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="key", type="string", example="welcome"),
     *             @OA\Property(property="locale", type="string", example="en"),
     *             @OA\Property(property="value", type="string", example="Updated welcome message."),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), example={1,2})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Translation updated successfully."),
     *     @OA\Response(response=404, description="Translation not found."),
     *     @OA\Response(response=422, description="Validation error.")
     * )
     */
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        try {
            $translation = $this->translationService->update($id, $request->validated());
            return $this->successResponse($this->transformer->transform($translation));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Translation not found (id: {$id})", (string) 404);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while updating the translation.", (string) 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/translations/{id}",
     *     summary="Delete a translation",
     *     tags={"Translations"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Translation id", @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Translation deleted successfully."),
     *     @OA\Response(response=404, description="Translation not found.")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->translationService->delete($id);
            return $this->successResponse(null, (string) 204);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Translation not found (id: {$id})", (string) 404);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while deleting the translation.", (string) 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/translations/export",
     *     summary="Export translations (grouped by key, with locale=>value and tags) for frontend use",
     *     tags={"Translations"},
     *     @OA\Response(response=200, description="A JSON export of translations (grouped by key).")
     * )
     */
    public function export(): JsonResponse
    {
        try {
            $exportData = $this->translationService->export();
            return $this->successResponse($exportData);
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while exporting translations.", (string) 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/translations/search",
     *     summary="Search translations by key, value, tag, or locale",
     *     tags={"Translations"},
     *     @OA\Parameter(name="key", in="query", description="Search by key (partial match)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="value", in="query", description="Search by value (partial match)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="tag", in="query", description="Search by tag (exact match)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="locale", in="query", description="Filter by locale", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="A list of translations matching the search criteria (grouped by key)."),
     *     @OA\Response(response=500, description="An error occurred while searching.")
     * )
     */
    public function search(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $filters = [
                'key' => $request->query('key'),
                'value' => $request->query('value'),
                'tag' => $request->query('tag'),
                'locale' => $request->query('locale'),
            ];
            $translations = $this->translationService->search($filters);
            return $this->successResponse($this->transformer->transformCollection($translations));
        } catch (\Exception $e) {
            return $this->errorResponse("An error occurred while searching translations.", (string) 500);
        }
    }
} 