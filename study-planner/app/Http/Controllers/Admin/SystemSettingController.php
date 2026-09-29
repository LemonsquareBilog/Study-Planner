<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    /**
     * List all system settings.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = SystemSetting::orderBy('key')->get()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'typed_value' => $setting->typed_value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        });

        return response()->json(['data' => $settings, 'message' => 'Settings retrieved successfully.']);
    }

    /**
     * Update a specific system setting.
     */
    public function update(UpdateSystemSettingRequest $request, SystemSetting $setting): JsonResponse
    {
        $setting->update($request->validated());

        return response()->json([
            'data' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'typed_value' => $setting->typed_value,
                'type' => $setting->type,
                'description' => $setting->description,
            ],
            'message' => 'Setting updated successfully.',
        ]);
    }
}
