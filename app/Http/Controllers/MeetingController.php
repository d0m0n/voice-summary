<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $meetings = Meeting::with('creator', 'summaries')
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
}
