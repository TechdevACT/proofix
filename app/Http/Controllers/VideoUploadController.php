<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VideoUploadController extends Controller
{
    /**
     * Handle chunked video upload.
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'chunk' => 'required|file',
            'chunkIndex' => 'required|integer',
            'totalChunks' => 'required|integer',
            'fileName' => 'required|string',
            'uploadId' => 'required|string', // Unique ID for this upload session
        ]);

        $chunk = $request->file('chunk');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');
        $uploadId = $request->input('uploadId');
        $originalFileName = $request->input('fileName');

        // We use a temporary directory for chunks
        $tempPath = storage_path('app/temp_uploads/' . $uploadId);
        
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        // Move the chunk to the temp folder
        $chunkName = $chunkIndex . '.part';
        $chunk->move($tempPath, $chunkName);

        // Check if all chunks are uploaded
        if ($this->checkAllChunksReceived($tempPath, $totalChunks)) {
            return $this->assembleFile($tempPath, $totalChunks, $originalFileName);
        }

        return response()->json(['message' => 'Chunk uploaded successfully', 'progress' => round(($chunkIndex + 1) / $totalChunks * 100)]);
    }

    private function checkAllChunksReceived($tempPath, $totalChunks)
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!file_exists($tempPath . '/' . $i . '.part')) {
                return false;
            }
        }
        return true;
    }

    private function assembleFile($tempPath, $totalChunks, $originalFileName)
    {
        $finalFileName = Str::uuid() . '_' . time() . '.mp4';
        $finalPath = storage_path('app/public/videos/' . $finalFileName);
        
        // Ensure destination directory exists
        if (!file_exists(dirname($finalPath))) {
            mkdir(dirname($finalPath), 0777, true);
        }

        $finalFile = fopen($finalPath, 'w');

        // Append all chunks
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempPath . '/' . $i . '.part';
            $chunkFile = fopen($chunkPath, 'r');
            stream_copy_to_stream($chunkFile, $finalFile);
            fclose($chunkFile);
            unlink($chunkPath); // Delete chunk after appending
        }

        fclose($finalFile);
        
        // Clean up temp directory, ensuring it's empty
        rmdir($tempPath); 

        return response()->json([
            'message' => 'File uploaded and assembled successfully',
            'file_path' => '/storage/videos/' . $finalFileName
        ]);
    }
}
