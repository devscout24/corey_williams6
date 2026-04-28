<?php

namespace App\Http\Controllers;

use App\Models\PhpposAppFile;
use Illuminate\Http\Response;

class AppFileController extends Controller
{
    public function view($fileId)
    {
        $file = PhpposAppFile::findOrFail($fileId);
        
        return response($file->file_data)
            ->header('Content-Type', $this->getMimeType($file->file_name))
            ->header('Cache-Control', 'max-age=86400');
    }

    private function getMimeType($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
        ];
        
        return $mimes[$ext] ?? 'application/octet-stream';
    }
}
