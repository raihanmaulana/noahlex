<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\ProjectStage;
use App\Models\ProjectDocument;
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

    public function pipeline()
    {
        
        $types = ProjectType::where('isDeleted', false)->pluck('name', 'id');

        
        $locations = Project::where('isDeleted', false)
            ->whereNotNull('location') 
            ->get(['id', 'name', 'type_id', 'size', 'location']);

        $totalLocations = $locations->count();

        $breakdownByStageRaw = Project::select(
            'stage_id',
            'type_id',
            DB::raw('SUM(REPLACE(size, " MW", "")) as total_mw')
        )
            ->where('isDeleted', false)
            ->groupBy('stage_id', 'type_id')
            ->get();

        $breakdownByStage = [];
        foreach (ProjectStage::all() as $stage) {
            $row = ['stage' => $stage->name];
            foreach ($types as $typeId => $typeName) {
                $row[strtolower($typeName)] = 0; 
            }
            foreach ($breakdownByStageRaw as $item) {
                if ($item->stage_id == $stage->id) {
                    $typeName = strtolower($types[$item->type_id] ?? 'unknown');
                    $row[$typeName] = (float) $item->total_mw;
                }
            }
            $breakdownByStage[] = $row;
        }

        
        $breakdownByYearRaw = Project::select(
            DB::raw('YEAR(cod_date) as cod_year'),
            'type_id',
            DB::raw('SUM(REPLACE(size, " MW", "")) as total_mw')
        )
            ->where('isDeleted', false)
            ->whereNotNull('cod_date')
            ->groupBy(DB::raw('YEAR(cod_date)'), 'type_id')
            ->get();

        $years = $breakdownByYearRaw->pluck('cod_year')->unique()->sort();
        $breakdownByYear = [];
        foreach ($years as $year) {
            $row = ['year' => $year];
            foreach ($types as $typeId => $typeName) {
                $row[strtolower($typeName)] = 0; 
            }
            foreach ($breakdownByYearRaw as $item) {
                if ($item->cod_year == $year) {
                    $typeName = strtolower($types[$item->type_id] ?? 'unknown');
                    $row[$typeName] = (float) $item->total_mw;
                }
            }
            $breakdownByYear[] = $row;
        }

        return response()->json([
            'success' => true,
            'total_locations' => $totalLocations,
            'locations' => $locations,
            'breakdown_by_stage' => $breakdownByStage,
            'breakdown_by_cod_year' => $breakdownByYear
        ]);
    }
}
