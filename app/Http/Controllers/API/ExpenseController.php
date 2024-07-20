<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
     /**
     * Store a newly created expense record in storage.
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

        // Create a new expense record with the authenticated user's ID as created_for_id
        $expense = Expense::create([
            'amount' => $request->input('amount'),
            'date' => $request->input('date'),
            'item_id' => $request->input('item_id'),
            'note' => $request->input('note'),
            'created_for_id' => $userId,
            'created_by_id' => $userId, // Optional: Set this if you also want to track who created it
        ]);

        // Return the newly created expense record as JSON response
        return response()->json($expense, 201);
    }

    /**
     * Display a listing of expenses created by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Get the authenticated user's ID
        $userId = Auth::id();

        // Retrieve expenses where created_for_id is the authenticated user's ID
        $expenses = Expense::where('created_for_id', $userId)->get();

        // Return expenses as JSON response
        return response()->json($expenses);
    }

    /**
     * Generate a report of total expenses within a specific date range.
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

        // Calculate the total expense amount within the specified date range
        $totalExpense = Expense::where('created_for_id', $userId)
            ->whereBetween('date', [$request->input('from_date'), $request->input('to_date')])
            ->sum('amount');

        // Return the total expense amount as JSON response
        return response()->json(['total_expense' => $totalExpense]);
    }
}
