<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $meetings = Meeting::with('creator', 'summaries')
            ->withCount('recordings')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('meetings.index', compact('meetings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('meetings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'required|string|max:10'
        ]);

        Meeting::create([
            'name' => $request->name,
            'description' => $request->description,
            'language' => $request->language,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('meetings.index')
            ->with('success', '会議が作成されました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting): View
    {
        $meeting->load('summaries.meeting');
        
        return view('meetings.show', compact('meeting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Meeting $meeting): View
    {
        return view('meetings.edit', compact('meeting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'required|string|max:10',
            'status' => 'required|in:active,completed,archived'
        ]);

        $meeting->update($request->only('name', 'description', 'language', 'status'));

        return redirect()->route('meetings.index')
            ->with('success', '会議が更新されました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting): RedirectResponse
    {
        $meeting->delete();

        return redirect()->route('meetings.index')
            ->with('success', '会議が削除されました。');
    }

    /**
     * Export summaries as CSV
     */
    public function exportCsv(Meeting $meeting): StreamedResponse
    {
        $summaries = $meeting->summaries()->with('user')->orderBy('created_at', 'desc')->get();

        $filename = "要約履歴_{$meeting->name}_" . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($summaries) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8 support
            fwrite($file, "\xEF\xBB\xBF");

            // CSV header
            fputcsv($file, ['入力日', '入力者名', '要約テキスト', '元のテキスト']);

            foreach ($summaries as $summary) {
                fputcsv($file, [
                    "'" . $summary->created_at->format('Y/m/d H:i:s'),
                    "'" . ($summary->user ? $summary->user->name : '不明'),
                    "'" . $summary->summary,
                    "'" . $summary->original_text
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export summaries as TXT
     */
    public function exportTxt(Meeting $meeting): Response
    {
        $summaries = $meeting->summaries()->with('user')->orderBy('created_at', 'desc')->get();

        $filename = "要約履歴_{$meeting->name}_" . now()->format('Y-m-d_H-i-s') . '.txt';

        $content = "会議名: {$meeting->name}\n";
        $content .= "エクスポート日時: " . now()->format('Y/m/d H:i:s') . "\n";
        $content .= str_repeat("=", 50) . "\n\n";

        foreach ($summaries as $summary) {
            $content .= "■ 入力日: " . $summary->created_at->format('Y/m/d H:i:s') . "\n";
            $content .= "■ 入力者: " . ($summary->user ? $summary->user->name : '不明') . "\n";
            $content .= "■ 要約テキスト:\n" . $summary->summary . "\n\n";
            $content .= "■ 元のテキスト:\n" . $summary->original_text . "\n";
            $content .= str_repeat("-", 50) . "\n\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
