<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of items created by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get the authenticated user's ID
        $userId = Auth::id();

        $query = Item::where('created_for_id', $userId);

        // Check if the request has a 'type' key and filter items by type if present
        if ($request->has('type')) {
            $type = $request->input('type');
            $query->where('type', $type);
        }

        // Execute the query and get the items
        $items = $query->get();

        // Return items as JSON response
        return response()->json($items);
    }

    /**
     * Store a newly created item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:1,2'
        ]);

        // Get the authenticated user's ID
        $userId = Auth::id();

        // Check if an item with the same name and type already exists for the authenticated user
        $existingItem = Item::where('name', $request->input('name'))
            ->where('type', $request->input('type'))
            ->where('created_for_id', $userId)
            ->first();

        if ($existingItem) {
            // Return a JSON response indicating that the item already exists
            return response()->json([
                'message' => 'Item with the same name and type already exists for this user.'
            ], 409); // 409 Conflict status code
        }

        // Create a new item with the authenticated user's ID as created_for_id
        $item = Item::create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'created_for_id' => $userId,
            'created_by_id' => $userId, // Optional: Set this if you also want to track who created it
        ]);

        // Return the newly created item as JSON response
        return response()->json($item, 201);
    }
}
