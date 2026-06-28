<?php


namespace App\Http\Controllers\FileManager;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Services\File\FileService;
use Illuminate\Support\Facades\URL;

class FileUploadController
{
    use ApiResponseTrait;
    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }
    public function uploadMultipleFile(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240'
        ]);

        $uploaded = $this->fileService->uploadMultipleFile(
            $request->file('files')
        );
        return $this->successResponse($uploaded,'Files uploaded successfully');
    }

    public function deleteFile(string $filename)
    {
        $deleted = $this->fileService->deleteFile($filename);

        abort_if(!$deleted, 404, 'File not found');

        return $this->successResponse(null,'File deleted successfully');
    }

    public function serveFile(string $filename)
    {
        return $this->fileService->downloadFile($filename);
    }
}
