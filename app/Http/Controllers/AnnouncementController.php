<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Announcement::query()
            ->with('creator')
            ->when($search, fn ($q) => $q->whereRaw('title COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"])
                ->orWhereRaw('content COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
            ->orderBy('starts_at', 'desc');

        return Inertia::render('Announcements/Index', [
            'announcements' => $query->paginate(10),
            'filters' => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('Announcements/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,danger',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('announcements.index')->with('success', 'Annonce créée avec succès.');
    }

    public function edit(Announcement $announcement)
    {
        return Inertia::render('Announcements/Edit', [
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,danger',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('announcements.index')->with('success', 'Annonce mise à jour avec succès.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('announcements.index')->with('success', 'Annonce supprimée avec succès.');
    }

    public function active()
    {
        return response()->json([
            'announcements' => Announcement::active()
                ->orderBy('starts_at', 'desc')
                ->get(['id', 'title', 'content', 'type', 'starts_at', 'ends_at']),
        ]);
    }
}
