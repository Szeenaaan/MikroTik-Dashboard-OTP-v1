<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\DataUsageQueryDto;
use Illuminate\Http\Request;
use App\Exports\UsersExport;
use App\Exports\SessionsExport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
    }

    public function index(Request $request)
    {
        $dto = new DataUsageQueryDto(
            type: $request->type,
            period: $request->period ?? 'd',
        );

        $data = $this->dashboardService->getDateData($dto);

        $chartData = $this->dashboardService->getChartData($dto);

        return view('dashboard.dashboard', compact('data', 'chartData'));
    }

    public function users(Request $request)
    {
        $users = $this->dashboardService->getUsers(
            search: $request->search,
            status: $request->status,
            fromDate: $request->from_date,
            toDate: $request->to_date,
            order: $request->order ?? 'desc',
        );

        return view('dashboard.users', compact('users'));
    }

    public function blockUser(int $id)
    {
        return $this->dashboardService->blockUser($id);
    }

    public function unblockUser(int $id)
    {
        return $this->dashboardService->unblockUser($id);
    }

    public function sessions(Request $request)
    {
        $sessions = $this->dashboardService->getSessions(
            status: $request->status,
            fromDate: $request->from_date,
            toDate: $request->to_date,
            order: $request->order ?? 'desc',
            search: $request->search,
        );

        return view('dashboard.sessions', compact('sessions'));
    }

    public function terminateSession(int $id)
    {
        return $this->dashboardService->terminateSession($id);
    }

    public function bypassUser(int $id)
    {
        return $this->dashboardService->bypassUser($id);
    }

     public function removeBypassUser(int $id)
    {
        return $this->dashboardService->removeBypassUser($id);
    }

    public function exportUsers(Request $request)
    {
        return Excel::download(
            new UsersExport(
                search: $request->search,
                status: $request->status,
                fromDate: $request->from_date,
                toDate: $request->to_date,
                order: $request->order ?? 'desc',
            ),
            'users.xlsx'
        );
    }

    public function exportSessions(Request $request)
    {
        return Excel::download(
            new SessionsExport(
                search: $request->search,
                status: $request->status,
                fromDate: $request->from_date,
                toDate: $request->to_date,
                order: $request->order ?? 'desc',
            ),
            'sessions.xlsx'
        );
    }
}