<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // User::truncate();

        $query = User::query();

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        return User::create($request->validated());
    }

        /**
     * Store a newly created resource in storage.
     */
    public function login(StoreRequest $request)
    {
        return User::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(User $User)
    {
        return $User;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $User)
    {
        $User->update($request->validated());

        return $User;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $User)
    {
        $User->delete();

        return response()->noContent();
    }
}
