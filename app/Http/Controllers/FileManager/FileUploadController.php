<?php


namespace App\Http\Controllers\FileManager;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Services\File\FileService;

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
            'loan_id'  => 'required|string',
            'files'    => 'required|array',
            'files.*'  => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240'
        ]);

        $uploaded = $this->fileService->uploadMultipleFile(
            $request->file('files'),
            $request->input('loan_id')
        );
        return $this->successResponse($uploaded,'Files uploaded successfully');
    }

    public function deleteFile(string $filename)
    {
        $deleted = $this->fileService->deleteFile($filename);

        abort_if(!$deleted, 404, 'File not found');

        return $this->successResponse(null,'File deleted successfully');
    }

    public function deleteLoanFile(string $loanId, string $filename)
    {
        $deleted = $this->fileService->deleteFile($filename, $loanId);

        abort_if(!$deleted, 404, 'File not found');

        return $this->successResponse(null,'File deleted successfully');
    }

    public function serveFile(string $filename)
    {
        $file = $this->fileService->serveFile($filename);

        return response()->stream(function () use ($file) {
            fpassthru($file['file_stream']);

            if (is_resource($file['file_stream'])) {
                fclose($file['file_stream']);
            }
        }, 200, [
            'Content-Type'         => $file['mime_type'],
            'Content-Disposition'  => 'inline; filename="' . $file['file_name'] . '"',
            'Cache-Control'        => 'no-cache, must-revalidate',
            'Content-Length'       => $file['file_size'],
        ]);
    }

    public function serveLoanFile(string $loanId, string $filename)
    {
        $file = $this->fileService->serveFile($filename, $loanId);

        return response()->stream(function () use ($file) {
            fpassthru($file['file_stream']);

            if (is_resource($file['file_stream'])) {
                fclose($file['file_stream']);
            }
        }, 200, [
            'Content-Type'         => $file['mime_type'],
            'Content-Disposition'  => 'inline; filename="' . $file['file_name'] . '"',
            'Cache-Control'        => 'no-cache, must-revalidate',
            'Content-Length'       => $file['file_size'],
        ]);
    }
}
