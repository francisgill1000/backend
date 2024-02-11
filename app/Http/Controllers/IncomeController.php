<?php

namespace App\Http\Controllers;

use App\Http\Requests\Income\StoreRequest;
use App\Models\Income;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Income::query();

        // Check if start_date and end_date are provided
        if (request()->has('start_date') && request()->has('end_date')) {
            $startDate = request('start_date');
            $endDate = request('end_date');
            // Apply the date range scope
            $query->filter($startDate, $endDate);
        }

        // Paginate the results (default to 10 items per page if not specified)
        $query->orderBy("id", "desc");

        return $query->paginate(request('itemsPerPage') ?? 15);
    }

    public function todayIncome()
    {
        $query = Income::query();
        $query->filter(date("Y-m-24"), date("Y-m-24"));
        return number_format($query->sum("amount"), 2);
    }

    public function weeklyIncome()
    {
        $query = Income::query();
        $query->filter(now()->startOfWeek(), now()->endOfWeek());
        return number_format($query->sum("amount"), 2);
    }

    public function monthlyIncome()
    {
        return number_format(Income::whereMonth("date", date("m"))->sum("amount"), 2);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        return Income::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Income $Income)
    {
        return $Income;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Income $Income)
    {
        $Income->update($request->validated());

        return $Income;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Income $Income)
    {
        $Income->delete();

        return response()->noContent();
    }
}
