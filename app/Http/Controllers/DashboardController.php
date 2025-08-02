<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();


        $activeProjectsCount = Project::where('isDeleted', false)
            ->where('status_id', '!=', 6)
            ->count();

        $activeProjectsLastMonth = Project::where('isDeleted', false)
            ->where('status_id', '!=', 6)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();


        $completedProjectsCount = Project::where('isDeleted', false)
            ->where('status_id', 6)
            ->count();

        $completedProjectsLastMonth = Project::where('isDeleted', false)
            ->where('status_id', 6)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $pendingApprovalsCount = ProjectDocument::where('isDeleted', false)
            ->where('status_id', 1)
            ->count();

        $pendingApprovalsLastMonth = ProjectDocument::where('isDeleted', false)
            ->where('status_id', 1)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $nearExpiredCount = ProjectDocument::where('isDeleted', false)
            ->where('status_id', 1)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$now, $now->copy()->addDays(7)])
            ->count();

        $nearExpiredLastMonth = ProjectDocument::where('isDeleted', false)
            ->where('status_id', 1)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        if (request()->filled('search')) {
            $search = request()->query('search');

            $searchResults = Project::where('isDeleted', false)
                ->where('name', 'like', '%' . $search . '%')
                ->get(['id', 'name', 'location', 'size', 'cover', 'status_id', 'created_at']);

            $latestProjects = []; 
        } else {
            $searchResults = []; 

            $latestProjects = Project::where('isDeleted', false)
                ->latest()
                ->take(6)
                ->get(['id', 'name', 'location', 'size', 'cover', 'status_id', 'created_at']);
        }
        
        return response()->json([
            'success' => true,
            'summary' => [
                'active_projects' => [
                    'count' => $activeProjectsCount,
                    'change' => $activeProjectsCount - $activeProjectsLastMonth
                ],
                'pending_approvals' => [
                    'count' => $pendingApprovalsCount,
                    'change' => $pendingApprovalsCount - $pendingApprovalsLastMonth
                ],
                'near_expired' => [
                    'count' => $nearExpiredCount,
                    'change' => $nearExpiredCount - $nearExpiredLastMonth
                ],
                'completed_projects' => [
                    'count' => $completedProjectsCount,
                    'change' => $completedProjectsCount - $completedProjectsLastMonth
                ],
            ],
            'latest_projects' => $latestProjects,
            'search_results' => $searchResults
        ]);
    }
}
