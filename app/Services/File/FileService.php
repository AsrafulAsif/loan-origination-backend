<?php

namespace App\Services\File;

use App\Models\File\FileManager;
use App\Traits\UserSnapshotTrait;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileService
{
    use UserSnapshotTrait;
    protected string $disk = 'public';

    public function uploadMultipleFile(array $files, string $loanId): array
    {
        $uploaded = [];
        $loanFolder = $this->sanitizeFolderName($loanId);

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $extension    = $file->getClientOriginalExtension();
            $storedName   = Str::uuid() . '.' . $extension;

            try {
                $path = $file->storeAs("uploads/{$loanFolder}", $storedName, $this->disk);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                continue;
            }

            FileManager::create([
                'loan_id'     => $loanId,
                'file_name'   => $originalName,
                'file_path'   => $path,
                'file_type'   => $file->getMimeType(),
                'uploaded_by' => $this->getUserSnapshot(),
                'uploaded_at' => now(),
                'file_size'   => $file->getSize(),
            ]);

            $uploaded[] = [
                'loan_id' => $loanId,
                'original_name' => $originalName,
                'filename'      => $storedName,
                'path'          => $path,
                'url'           => Storage::disk($this->disk)->url($path),
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
            ];
        }

        return $uploaded;
    }

    public function deleteFile(string $filename, ?string $loanId = null): bool
    {
        $path = $this->resolvePath($filename, $loanId);

        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        FileManager::where('file_path', $path)->delete();

        return Storage::disk($this->disk)->delete($path);
    }

    public function serveFile(string $filename, ?string $loanId = null): array
    {
        $path = $this->resolvePath($filename, $loanId);

        abort_if(
            !Storage::disk($this->disk)->exists($path),
            404,
            'File not found'
        );

        $stream = Storage::disk($this->disk)->readStream($path);

        abort_if($stream === false, 500, 'Unable to read file');

        return [
            'file_stream' => $stream,
            'mime_type' => Storage::disk($this->disk)->mimeType($path) ?? 'application/octet-stream',
            'file_name' => basename($filename),
            'file_size' => Storage::disk($this->disk)->size($path),
        ];
    }

    private function resolvePath(string $filename, ?string $loanId = null): string
    {
        if ($loanId) {
            return 'uploads/' . $this->sanitizeFolderName($loanId) . '/' . basename($filename);
        }

        return 'uploads/' . basename($filename);
    }

    private function sanitizeFolderName(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/[^A-Za-z0-9._-]/', '_')
            ->toString();
    }
}
