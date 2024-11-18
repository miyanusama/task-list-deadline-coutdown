<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::paginate(5);
        return view('tasks.index', compact('tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'deadline' => 'required|date|after:now',
        ]);

        Task::create($request->all());

        return response()->json(['success' => true]);
    }

    // Show task data for editing (AJAX request handler)
    public function edit(Task $task)
    {
        return response()->json([
            'success' => true,
            'task' => $task
        ]);
    }

    // Update task logic (AJAX request handler)
    public function update(Request $request, Task $task)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date|after:today',
        ]);

        $task->update([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['success' => true]);
    }
}
