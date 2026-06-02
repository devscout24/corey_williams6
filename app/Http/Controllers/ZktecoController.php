<?php

namespace App\Http\Controllers;

use App\Services\ZktecoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZktecoController extends Controller
{
    public function __construct(
        protected ZktecoService $zkteco,
    ) {}

    public function index(): View
    {
        $ip = $this->zkteco->getIp();
        $port = $this->zkteco->getPort();

        return view('zkteco.index', compact('ip', 'port'));
    }

    public function connect(): JsonResponse
    {
        $result = $this->zkteco->testConnection();

        return response()->json($result);
    }

    public function attendance(): JsonResponse
    {
        $result = $this->zkteco->getAttendance();

        return response()->json($result);
    }

    public function saveConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        $this->zkteco->saveConfig($data['ip'], (int) $data['port']);

        return response()->json(['success' => true]);
    }
}
