<?php

namespace App\Http\Controllers;

use App\Models\PrizeWinner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function verify(string $token): View
    {
        $winner = PrizeWinner::where('claim_token', $token)
            ->with(['employee', 'prize.lotteryEvent'])
            ->first();

        if (! $winner) {
            abort(404, '找不到領獎資訊');
        }

        return view('claim.verify', compact('winner'));
    }

    public function apiVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
            'prize_id' => 'required|integer',
        ]);

        $winner = PrizeWinner::where('claim_token', $validated['token'])
            ->where('prize_id', $validated['prize_id'])
            ->with(['employee', 'prize'])
            ->first();

        if (! $winner) {
            return response()->json([
                'success' => false,
                'message' => '無效的領獎代碼或不屬於此獎項',
            ], 404);
        }

        if ($winner->isClaimed()) {
            return response()->json([
                'success' => false,
                'message' => '此獎項已於 '.$winner->claimed_at->format('Y/m/d H:i').' 領取',
                'winner' => [
                    'name' => $winner->employee->name,
                    'department' => $winner->employee->department,
                    'sequence' => $winner->sequence,
                    'claimed_at' => $winner->claimed_at->toIso8601String(),
                ],
            ], 409);
        }

        $winner->update(['claimed_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => '領獎成功',
            'winner' => [
                'name' => $winner->employee->name,
                'department' => $winner->employee->department,
                'sequence' => $winner->sequence,
                'prize_name' => $winner->prize->name,
                'claimed_at' => $winner->claimed_at->toIso8601String(),
            ],
        ]);
    }
}
