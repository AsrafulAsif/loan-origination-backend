<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

require __DIR__ . '/Auth/AuthRoutes.php';
require __DIR__ . '/Privilege/RoleRoute.php';
require __DIR__ . '/Privilege/PermissionRoute.php';
require __DIR__ . '/AccountDetails/AccountDetailsRoute.php';
require __DIR__ . '/Product/ProductRoute.php';
require __DIR__ . '/Product/ProductDetailsRoute.php';
require __DIR__ . '/Workflow/WorkflowRoute.php';
require __DIR__ . '/LoanOrigination/LoanOriginationRoute.php';
require __DIR__ . '/Template/TemplateRoute.php';
require __DIR__ . '/FileUpload/FileUpload.php';
require __DIR__ . '/Chat/LoanChatMessageRoute.php';
require __DIR__ . '/ExternalApi/ExternalApiRoute.php';

