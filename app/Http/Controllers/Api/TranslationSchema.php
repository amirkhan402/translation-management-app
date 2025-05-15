<?php
/**
 * @OA\Schema(
 *     schema="Translation",
 *     type="object",
 *     required={"id", "key", "locale", "value"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="key", type="string", example="welcome"),
 *     @OA\Property(property="locale", type="string", example="en"),
 *     @OA\Property(property="value", type="string", example="Welcome to our application"),
 *     @OA\Property(
 *         property="tags",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"id", "name"},
 *             @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002"),
 *             @OA\Property(property="name", type="string", example="common")
 *         )
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-19T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-19T12:00:00Z")
 * )
 */
