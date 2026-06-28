<?php

namespace App\Services\File;

use App\Models\File\FileManager;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileService
{
    protected string $disk = 'public';

    public function uploadMultipleFile(array $files): array
    {
        $uploaded = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $extension    = $file->getClientOriginalExtension();
            $storedName   = Str::uuid() . '.' . $extension;

            try {
                $path = $file->storeAs('uploads', $storedName, $this->disk);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

           FileManager::create([
                'file_name'   => $originalName,
                'file_path'   => $path,
                'file_type'   => $file->getMimeType(),
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
                'file_size'   => $file->getSize(),
            ]);

            $uploaded[] = [
                'original_name' => $originalName,
                'filename'      => $storedName,
                'url'           => Storage::disk($this->disk)->url($path),
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
            ];
        }

        return $uploaded;
    }

    public function deleteFile(string $filename): bool
    {
        $path = 'uploads/' . $filename;

        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        FileManager::where('file_path', $path)->delete();

        return Storage::disk($this->disk)->delete($path);
    }

    public function downloadFile(string $filename): Response|ResponseFactory
    {
        $path = 'uploads/' . $filename;

        abort_if(
            !Storage::disk($this->disk)->exists($path),
            404,
            'File not found'
        );

        $mimeType    = Storage::disk($this->disk)->mimeType($path);
        $fileContent = Storage::disk($this->disk)->get($path);
        return response($fileContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }
}
