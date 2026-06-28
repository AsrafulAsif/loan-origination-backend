<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\GetAllUsersRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use App\Services\EmailService\EmailService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    use ApiResponseTrait;

    protected AuthService $authService;
    public function __construct(AuthService $authService){
        $this->authService = $authService;
    }

    public function getAllUsers(GetAllUsersRequest $request): JsonResponse
    {
        $users = $this->authService->getAllUsers($request->validated());
        return $this->paginatedResponse($users, 'Users retrieved successfully');
    }

    public function getAUser(Request $request): JsonResponse
    {
        $user = $this->authService->getUserByEmail($request->string('email'));
        return $this->successResponse($user, 'User retrieved successfully');
    }

    public function getAUserByEmployeeId(): JsonResponse
    {
        return $this ->successResponse($this->authService->getAUserByEmployeeId());
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $responseData = $this->authService->login($request->validated());
        return $this->successResponse($responseData['data'])
            ->withCookie(
                cookie(
                    'refresh_token',
                    $responseData['refresh_token'],
                    60 * 24 * 30,
                    '/',
                    null,
                    false,
                    true,
                    false,
                    'Lax'
                )
            );
    }

    public function refresh(Request $request): JsonResponse
    {
        $responseData = $this->authService->refresh($request);
        return $this->successResponse($responseData['data'])
            ->withCookie(
                cookie(
                    'refresh_token',
                    $responseData['refresh_token'],
                    60 * 24 * 30,
                    '/',
                    null,
                    false,
                    true,
                    false,
                    'Lax'
                )
            );
    }

    public function logout()
    {
        $this->authService->logout();
        return $this->successResponse(null, 'User logged out successfully')
            ->withCookie(
                cookie()->forget('refresh_token')
            );
    }

}
