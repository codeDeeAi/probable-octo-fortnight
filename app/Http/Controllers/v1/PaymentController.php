<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\v1\PaymentService;
use App\Traits\v1\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected PaymentService $service;

    public function __construct()
    {
        $this->service = new PaymentService(user: request()->user());
    }

    public function makePayment(int $bookingId): JsonResponse
    {

        $data = $this->service->processPayment(
            bookingId: $bookingId
        );

        return $this->apiResponse::created(
            message: 'Payment processed successfully.',
            data: $data
        );
    }

    public function show(int $paymentId): JsonResponse
    {
        $data = $this->service->getPaymentDetails(
            paymentId: $paymentId
        );

        return $this->apiResponse::success(
            message: 'Payment fetched successfully.',
            data: $data
        );
    }
}
