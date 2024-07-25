<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function getReport(Request $request)
    {
        // Validate the incoming request to ensure 'from_date' and 'to_date' are provided
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        // Retrieve the 'from_date' and 'to_date' from the request
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        $userId = Auth::id();
        // Calculate the total income within the date range
        $totalIncome = Income::whereBetween('date', [$fromDate, $toDate])->where('created_for_id', $userId)
            ->sum('amount');

        // Calculate the total expense within the date range
        $totalExpense = Expense::whereBetween('date', [$fromDate, $toDate])->where('created_for_id', $userId)
            ->sum('amount');

        // Calculate the total profit
        $totalProfit = $totalIncome - $totalExpense;

        // Return the report data
        return response()->json([
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_profit' => $totalProfit,
        ]);
    }

    public function getChartData()
    {
        // Get the current date
        $currentDate = Carbon::now();

        // Calculate the start date (12 months ago from the start of the current month)
        $startDate = $currentDate->copy()->subMonths(11)->startOfMonth();

        // Initialize arrays for labels, incomes, and expenses
        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $userId = Auth::id();

        // Loop through each month in the date range
        for ($date = $startDate; $date <= $currentDate; $date->addMonth()) {
            // Format the label as MM/YY
            $label = $date->format('m/y');
            $labels[] = $label;

            // Calculate total income for the current month
            $totalIncome = Income::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)->where('created_for_id', $userId)
                ->sum('amount');
            $incomeData[] = $totalIncome;

            // Calculate total expense for the current month
            $totalExpense = Expense::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)->where('created_for_id', $userId)
                ->sum('amount');
            $expenseData[] = $totalExpense;
        }

        // Return the chart data
        return response()->json([
            'labels' => $labels,
            'data' => [
                'incomes' => $incomeData,
                'expenses' => $expenseData,
            ],
        ]);
    }
}
