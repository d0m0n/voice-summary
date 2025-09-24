<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Summary;
use App\Services\ClaudeApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SummaryController extends Controller
{
    public function __construct(
        private ClaudeApiService $claudeApi
    ) {}

    public function generateSummary(Request $request, Meeting $meeting): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|min:10',
            'type' => 'required|in:auto,manual'
        ]);

        try {
            $summaryText = $this->claudeApi->generateSummary($request->text);

            $summary = Summary::create([
                'meeting_id' => $meeting->id,
                'original_text' => $request->text,
                'summary' => $summaryText,
                'type' => $request->type,
                'metadata' => [
                    'created_at' => now()->toISOString(),
                    'language' => $meeting->language,
                ]
            ]);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'message' => '要約が生成されました。'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '要約の生成に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSummaries(Meeting $meeting): JsonResponse
    {
        $summaries = $meeting->latestSummaries()
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'summaries' => $summaries
        ]);
    }

    public function deleteSummary(Summary $summary): JsonResponse
    {
        $summary->delete();

        return response()->json([
            'success' => true,
            'message' => '要約が削除されました。'
        ]);
    }
}
