<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\System\CreditController;
use App\Models\Model3D;
use App\Models\ModelPart;
use App\Services\TextureSuggestions\TextureSuggestionPipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TextureSuggestionController extends Controller
{
    public function suggest(
        Request $request,
        TextureSuggestionPipelineService $pipeline,
        CreditController $credits
    ): JsonResponse {
        @set_time_limit((int) config('texture_suggestions.request_time_limit', 180));

        $payload = $request->validate([
            'model3d_id' => ['required', 'integer', 'exists:model3ds,id'],
            'model_part_id' => ['required', 'integer', 'exists:model_parts,id'],
            'prompt' => ['nullable', 'string', 'max:500'],
            'style' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:6'],
            'screenshot' => ['nullable', 'string', 'max:8388608'],
        ]);

        $model = Model3D::query()->findOrFail($payload['model3d_id']);
        $part = ModelPart::query()
            ->where('model3d_id', $model->id)
            ->find($payload['model_part_id']);

        if (! $part) {
            return response()->json([
                'success' => false,
                'message' => 'Model part tidak cocok dengan model yang dipilih.',
            ], 422);
        }

        $creditCharge = ['charged' => false];

        try {
            $creditCharge = $credits->chargeForTexturePrompt($request->user());

            $result = $pipeline->generateForPart(
                $request->user(),
                $model,
                $part,
                [
                    'prompt' => $payload['prompt'] ?? null,
                    'style' => $payload['style'] ?? null,
                    'limit' => $payload['limit'] ?? null,
                    'screenshot' => $payload['screenshot'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Texture baru dari Gemini berhasil dibuat.',
                'credits' => $creditCharge,
            ] + $result);
        } catch (RuntimeException $exception) {
            $creditPayload = null;

            if (($creditCharge['charged'] ?? false) === true) {
                $creditPayload = $credits->refundTexturePrompt($request->user());
            } elseif ($exception->getCode() === 402) {
                $creditPayload = $credits->creditPayload($request->user());
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'credits' => $creditPayload,
            ], $exception->getCode() === 402 ? 402 : 503);
        }
    }
}
