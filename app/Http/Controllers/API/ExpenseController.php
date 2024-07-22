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
        $expenses = Expense::leftJoin('items', 'items.id', '=', 'expenses.item_id')
        ->select(['expenses.*', 'items.name as item_name'])
        ->where('expenses.created_for_id', $userId)
        ->get();

        // Return expenses as JSON response
        return response()->json($expenses);
    }


    
    /**
     * Update the specified expense record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
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

        // Find the expense by ID and check if it belongs to the authenticated user
        $expense = Expense::where('id', $id)
            ->where('created_for_id', $userId)
            ->first();

        if (!$expense) {
            // Return a JSON response indicating that the expense was not found
            return response()->json([
                'message' => 'Expense not found.'
            ], 404); // 404 Not Found status code
        }

        // Update the expense
        $expense->amount = $request->input('amount');
        $expense->date = $request->input('date');
        $expense->item_id = $request->input('item_id');
        $expense->note = $request->input('note');
        $expense->save();

        // Return the updated expense as JSON response
        return response()->json($expense);
    }

    /**
     * Remove the specified expense record from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        // Get the authenticated user's ID
        $userId = Auth::id();

        // Find the expense by ID and check if it belongs to the authenticated user
        $expense = Expense::where('id', $id)
            ->where('created_for_id', $userId)
            ->first();

        if (!$expense) {
            // Return a JSON response indicating that the expense was not found
            return response()->json([
                'message' => 'Expense not found.'
            ], 404); // 404 Not Found status code
        }

        // Delete the expense
        $expense->delete();

        // Return a JSON response indicating successful deletion
        return response()->json([
            'message' => 'Expense deleted successfully.'
        ], 200); // 200 OK status code
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
