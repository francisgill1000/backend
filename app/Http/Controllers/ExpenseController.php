<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreRequest;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Expense::query();

        // Check if start_date and end_date are provided
        if (request()->has('start_date') && request()->has('end_date')) {
            $startDate = request('start_date');
            $endDate = request('end_date');
            // Apply the date range scope
            $query->filter($startDate, $endDate);
        }

        $query->where("user_id", request("user_id") ?? 0);

        // Paginate the results (default to 10 items per page if not specified)
        $query->orderBy("id", "desc");

        return $query->paginate(request('itemsPerPage') ?? 15);
    }

    public function customExpense()
    {
        $query = Expense::query();

        $query->filter(request('start_date') ?? date("Y-m-d"), request('end_date') ?? date("Y-m-d"));

        $query->where("user_id", request("user_id") ?? 0);

        return $query->sum("amount");
    }

    public function todayExpense()
    {
        $query = Expense::query();
        $query->filter(date("Y-m-24"), date("Y-m-24"));
        return number_format($query->where("user_id", request("user_id") ?? 0)->sum("amount"), 2);
    }

    public function weeklyExpense()
    {
        $startDate = now()->subDays(7)->startOfDay();

        $endDate = now()->endOfDay();
    
        $query = Expense::query();

        $query->whereBetween('date', [$startDate, $endDate]);
        
        $query->where('user_id', request('user_id') ?? 0);
    
        return number_format($query->sum('amount'), 2);

    }

    public function monthlyExpense()
    {
        $model = Expense::whereMonth("date", date("m"))->where("user_id", request("user_id") ?? 0)->sum("amount");

        return number_format($model, 2);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        return Expense::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        return $expense;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $expense->update($request->validated());

        return $expense;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $Expense)
    {
        $Expense->delete();

        return response()->noContent();
    }
}
