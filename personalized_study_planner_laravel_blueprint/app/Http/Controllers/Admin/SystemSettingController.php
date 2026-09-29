<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Http\Resources\SystemSettingResource;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SystemSettingController extends Controller
{
    /**
     * List all system settings.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = SystemSetting::orderBy('group')->orderBy('sort_order')->orderBy('key')->get();

        return response()->json([
            'data' => SystemSettingResource::collection($settings),
            'message' => 'Settings retrieved successfully.',
        ]);
    }

    /**
     * Update a specific system setting.
     */
    public function update(UpdateSystemSettingRequest $request, SystemSetting $setting): JsonResponse
    {
        $setting->update($request->validated());

        return response()->json([
            'data' => new SystemSettingResource($setting->fresh()),
            'message' => 'Setting updated successfully.',
        ]);
    }

    /**
     * Bulk update system settings.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'exists:system_settings,key'],
            'settings.*.value' => ['required', 'string'],
            'settings.*.description' => ['nullable', 'string'],
            'settings.*.label' => ['nullable', 'string'],
            'settings.*.group' => ['nullable', 'string'],
            'settings.*.sort_order' => ['nullable', 'integer'],
            'settings.*.validation_rules' => ['nullable', 'json'],
            'settings.*.impact_description' => ['nullable', 'string'],
        ]);

        $updated = 0;
        $errors = [];

        DB::transaction(function () use ($request, &$updated, &$errors) {
            foreach ($request->input('settings') as $settingData) {
                $setting = SystemSetting::where('key', $settingData['key'])->first();
                
                if (!$setting) {
                    $errors[] = [
                        'key' => $settingData['key'],
                        'error' => 'Setting not found',
                    ];
                    continue;
                }

                try {
                    $setting->update([
                        'value' => $settingData['value'],
                        'description' => $settingData['description'] ?? $setting->description,
                        'label' => $settingData['label'] ?? $setting->label,
                        'group' => $settingData['group'] ?? $setting->group,
                        'sort_order' => $settingData['sort_order'] ?? $setting->sort_order,
                        'validation_rules' => $settingData['validation_rules'] ?? $setting->validation_rules,
                        'impact_description' => $settingData['impact_description'] ?? $setting->impact_description,
                    ]);
                    $updated++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'key' => $settingData['key'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'data' => [
                'updated' => $updated,
                'errors' => $errors,
            ],
            'message' => "Updated {$updated} setting(s)." . (count($errors) > 0 ? " {$count($errors)} error(s)." : ''),
        ], count($errors) > 0 ? 207 : 200);
    }
}
