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
                'message' => 'Duplicate item'
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

      /**
     * Update the specified item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:1,2'
        ]);

        // Get the authenticated user's ID
        $userId = Auth::id();

        // Find the item by ID and check if it belongs to the authenticated user
        $item = Item::where('id', $id)
            ->where('created_for_id', $userId)
            ->first();

        if (!$item) {
            // Return a JSON response indicating that the item was not found
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found status code
        }

        $existingItem = Item::where('id','!=',$id)
            ->where('name', $request->input('name'))
            ->where('type', $request->input('type'))
            ->where('created_for_id', $userId)
            ->first();

        if ($existingItem) {
            // Return a JSON response indicating that the item already exists
            return response()->json([
                'message' => 'Duplicate item'
            ], 409); // 409 Conflict status code
        }


        // Update the item
        $item->name = $request->input('name');
        $item->type = $request->input('type');
        $item->save();

        // Return the updated item as JSON response
        return response()->json($item);
    }

    /**
     * Remove the specified item from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        // Get the authenticated user's ID
        $userId = Auth::id();

        // Find the item by ID and check if it belongs to the authenticated user
        $item = Item::where('id', $id)
            ->where('created_for_id', $userId)
            ->first();

        if (!$item) {
            // Return a JSON response indicating that the item was not found
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found status code
        }

        // Delete the item
        $item->delete();

        // Return a JSON response indicating successful deletion
        return response()->json([
            'message' => 'Item deleted successfully.'
        ], 200); // 200 OK status code
    }
}
