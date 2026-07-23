<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base for API FormRequests across the app and its modules.
 *
 * A plain FormRequest renders validation failures as Laravel's default
 * {message, errors} 422, but every API endpoint here answers with the
 * ApiResponseTrait envelope {success, message, errors}. Mobile and
 * third-party clients read that envelope, so switching an endpoint from an
 * inline Validator::make to a typed FormRequest must not change the failure
 * shape. This base keeps it identical to validationErrorResponse().
 *
 * Typing a FormRequest into a controller method is also what lets Scramble
 * infer the request body schema for /docs, which the inline validators never
 * exposed. That is the point of moving to these. See zerp-pk/zerp#34.
 *
 * authorize() defaults to true because the API controllers run their own
 * granular can() permission checks; override here only when a request maps
 * cleanly to a single ability.
 */
abstract class ApiFormRequest extends FormRequest
{
    use ApiResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator->errors())
        );
    }
}
