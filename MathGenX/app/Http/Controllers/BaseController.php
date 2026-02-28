<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Send success response.
     */
    public function sendResponse($result, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ], 200);
    }

    /**
     * Send error response.
     */
    public function sendError(string $error, $errorMessages = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $error,
            'errors'  => $errorMessages,
        ], $code);
    }
}
