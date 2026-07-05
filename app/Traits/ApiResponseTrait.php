<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Success Response
     */
    protected function successResponse($data = null, $message = "Success", $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Error Response
     */
    protected function errorResponse($message = "Error", $errors = null, $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Validation Error Response
     */
    protected function validationErrorResponse($errors, $message = "Validation error", $status = 422)
    {
        return $this->errorResponse($message, $errors, $status);
    }

    /**
     * Pagination Response
     */
    protected function paginatedResponse($items, $message = "Fetched successfully")
    {
        return $this->successResponse([
            'items' => $items->items(),
            'pagination' => [
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
            ]
        ], $message);
    }
}