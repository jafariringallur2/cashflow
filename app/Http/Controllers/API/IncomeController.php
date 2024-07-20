<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
     /**
     * Store a newly created income record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'item_id' => 'required|exists:items,id',
            'note' => 'nullable|string',
        ]);

        // Get the authenticated user's ID
        $userId = Auth::id();

        // Create a new income record with the authenticated user's ID as created_for_id
        $income = Income::create([
            'amount' => $request->input('amount'),
            'date' => $request->input('date'),
            'item_id' => $request->input('item_id'),
            'note' => $request->input('note'),
            'created_for_id' => $userId,
            'created_by_id' => $userId, // Optional: Set this if you also want to track who created it
        ]);

        // Return the newly created income record as JSON response
        return response()->json($income, 201);
    }

    /**
     * Display a listing of incomes created by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Get the authenticated user's ID
        $userId = Auth::id();

        // Retrieve incomes where created_for_id is the authenticated user's ID
        $incomes = Income::where('created_for_id', $userId)->get();

        // Return incomes as JSON response
        return response()->json($incomes);
    }

    /**
     * Generate a report of total income within a specific date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function report(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        // Get the authenticated user's ID
        $userId = Auth::id();

        // Calculate the total income amount within the specified date range
        $totalIncome = Income::where('created_for_id', $userId)
            ->whereBetween('date', [$request->input('from_date'), $request->input('to_date')])
            ->sum('amount');

        // Return the total income amount as JSON response
        return response()->json(['total_income' => $totalIncome]);
    }
}
