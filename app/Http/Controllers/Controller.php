<?php

namespace App\Http\Controllers;


/**
 * @OA\Info(
 *     title="ServITech API",
 *     version="1.0.0",
 *     description="API documentation for ServITech"
 * )
 * 
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="errors", type="object")
 * )
 * 
 * @OA\Schema(
 *    schema="MessageResponse",
 *    type="object",
 *    @OA\Property(property="title", type="string", example="Success"),
 *    @OA\Property(property="type", type="string", example="success")
 * )
 */
abstract class Controller
{
    //
}
