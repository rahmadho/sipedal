<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Mengambil daftar berkas media dari direktori uploads dan aset bawaan.
     */
    public function index(): JsonResponse
    {
        $media = [];

        // 1. Berkas hasil unggahan pengguna
        $uploadPath = public_path('uploads/media');
        if (File::isDirectory($uploadPath)) {
            $files = File::files($uploadPath);
            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'svg', 'webp', 'gif'], true)) {
                    $media[] = [
                        'nama' => $file->getFilename(),
                        'url' => asset('uploads/media/' . $file->getFilename()),
                        'path' => 'uploads/media/' . $file->getFilename(),
                        'ukuran' => round($file->getSize() / 1024, 1) . ' KB',
                        'waktu' => date('d/m/Y H:i', $file->getMTime()),
                        'tipe' => 'unggahan',
                    ];
                }
            }
        }

        // 2. Berkas bawaan sistem (ilustrasi latar login)
        $loginBgPath = public_path('img/login');
        if (File::isDirectory($loginBgPath)) {
            $files = File::files($loginBgPath);
            foreach ($files as $file) {
                $media[] = [
                    'nama' => $file->getFilename(),
                    'url' => asset('img/login/' . $file->getFilename()),
                    'path' => 'img/login/' . $file->getFilename(),
                    'ukuran' => round($file->getSize() / 1024, 1) . ' KB',
                    'waktu' => 'Bawaan Sistem',
                    'tipe' => 'sistem',
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'data' => $media,
        ]);
    }

    /**
     * Mengunggah berkas baru.
     */
    public function upload(Request $request): JsonResponse
    {
        $peran = session('peran');
        if (!in_array($peran, ['super_admin', 'operator', 'admin_pbj'], true)) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Akses ditolak: peran Anda tidak dapat mengunggah media.',
            ], 403);
        }

        $request->validate([
            'berkas' => ['required', 'file', 'mimes:jpeg,png,jpg,webp,svg', 'max:4096'],
        ]);

        $file = $request->file('berkas');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Berkas tidak valid atau rusak.',
            ], 422);
        }

        $uploadDir = public_path('uploads/media');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $ext = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = time() . '_' . $safeName . '.' . $ext;

        $file->move($uploadDir, $filename);

        return response()->json([
            'ok' => true,
            'pesan' => 'Berkas berhasil diunggah.',
            'media' => [
                'nama' => $filename,
                'url' => asset('uploads/media/' . $filename),
                'path' => 'uploads/media/' . $filename,
            ],
        ]);
    }
}
