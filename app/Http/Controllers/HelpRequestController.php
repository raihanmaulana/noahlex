<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\HelpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\HelpRequestProgressUpdated;
use App\Mail\NewHelpRequestNotification;

class HelpRequestController extends Controller
{
    public function index()
    {
        $requests = HelpRequest::with('user', 'project')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:help_requests,id',
            'status' => 'required|in:pending,resolved,closed',
            'progress_message' => 'nullable|string'
        ]);

        $helpRequest = HelpRequest::findOrFail($request->id);
        $helpRequest->status = $request->status;
        $helpRequest->save();

        Mail::to($helpRequest->email)->send(
            new HelpRequestProgressUpdated($helpRequest, $request->progress_message)
        );

        return response()->json([
            'success' => true,
            'message' => 'Status updated and notification sent.'
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'issue_type' => 'required|string',
            'message' => 'required|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        $helpRequest = HelpRequest::create($data);

        $admins = User::where('role_id', 8)->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new NewHelpRequestNotification($helpRequest));
        }

        HelpRequest::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Help request submitted. We’ll respond ASAP!'
        ]);
    }
}
