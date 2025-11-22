<?php

declare(strict_types=1);

namespace App\Traits\v1;

use App\Libs\v1\ApiResponse;

/**
 * @property-read \App\Libs\v1\ApiResponse $apiResponse
 */
trait ApiResponseTrait
{
    public function __get(string $name): mixed
    {
        if ($name === 'apiResponse') {
            return ApiResponse::class;
        }

        throw new \Exception("Property {$name} does not exist.");
    }
}
