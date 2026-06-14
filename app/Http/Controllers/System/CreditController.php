<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditController extends Controller
{
    public const REGISTER_BONUS = 30;
    public const AI_TEXTURE_PROMPT_COST = 10;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'credits' => [
                'balance' => $this->balance($user, self::REGISTER_BONUS),
                'ai_texture_prompt_cost' => $this->texturePromptCost($user),
                'exempt' => $this->isExempt($user),
            ],
        ]);
    }

    public function ensureWallet(User $user, int $initialCredits = 0): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['credits' => $initialCredits]
        );
    }

    public function grantRegisterBonus(User $user): Wallet
    {
        return DB::transaction(function () use ($user) {
            return Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['credits' => self::REGISTER_BONUS]
            );
        });
    }

    public function balance(User $user, int $initialCredits = 0): int
    {
        return $this->ensureWallet($user, $initialCredits)->credits;
    }

    public function texturePromptCost(User $user): int
    {
        return $this->isExempt($user) ? 0 : self::AI_TEXTURE_PROMPT_COST;
    }

    public function chargeForTexturePrompt(User $user): array
    {
        if ($this->isExempt($user)) {
            return $this->creditPayload($user, false, 0);
        }

        $amount = self::AI_TEXTURE_PROMPT_COST;
        $this->ensureWallet($user, self::REGISTER_BONUS);

        return DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->credits < $amount) {
                throw new RuntimeException(
                    "Credits tidak cukup. Generate texture AI membutuhkan {$amount} credits.",
                    402
                );
            }

            $wallet->decrement('credits', $amount);
            $wallet->refresh();

            return [
                'charged' => true,
                'cost' => $amount,
                'balance' => $wallet->credits,
                'exempt' => false,
            ];
        });
    }

    public function refundTexturePrompt(User $user): array
    {
        if ($this->isExempt($user)) {
            return $this->creditPayload($user, false, 0);
        }

        $amount = self::AI_TEXTURE_PROMPT_COST;
        $this->ensureWallet($user, self::REGISTER_BONUS);

        return DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('credits', $amount);
            $wallet->refresh();

            return [
                'charged' => false,
                'refunded' => true,
                'cost' => $amount,
                'balance' => $wallet->credits,
                'exempt' => false,
            ];
        });
    }

    public function isExempt(User $user): bool
    {
        return $user->usertype === 'admin';
    }

    public function creditPayload(User $user, bool $charged = false, ?int $cost = null): array
    {
        return [
            'charged' => $charged,
            'cost' => $cost ?? $this->texturePromptCost($user),
            'balance' => $this->balance($user, self::REGISTER_BONUS),
            'exempt' => $this->isExempt($user),
        ];
    }
}
