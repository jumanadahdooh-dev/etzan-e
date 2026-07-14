<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\Concerns\PatientContextHelpers;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\CompleteProfileRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientMeal;
use App\Services\AiMealAnalysisService;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientDashboardController extends Controller
{
    use PatientContextHelpers;

    public function index(): RedirectResponse
    {
        return redirect()->route('patient.home');
    }


    public function home(): View
    {
        return view('patient.home', $this->dashboardData([
            'pageTitle' => 'الرئيسية',
            'activePage' => 'home',
        ]));
    }

}
