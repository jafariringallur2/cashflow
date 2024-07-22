<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ocw\AgGrid\Facades\AgGrid;

class DatatableController extends Controller
{
    public function getData(Request $request,$model){
        $userId = Auth::id();
        if($model === "income"){
            $incomes = Income::leftJoin('items', 'items.id', '=', 'incomes.item_id')
            ->select(['incomes.*', 'items.name as item_name'])
            ->where('incomes.created_for_id', $userId);
    
            $ag = AgGrid::of($incomes)->make();
            return $ag;
        }elseif($model === "expense"){
            $expenses = Expense::leftJoin('items', 'items.id', '=', 'expenses.item_id')
            ->select(['expenses.*', 'items.name as item_name'])
            ->where('expenses.created_for_id', $userId);
    
            $ag = AgGrid::of($expenses)->make();
            return $ag;
        }

        return [];
      
    }
}
