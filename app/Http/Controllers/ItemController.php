<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\StoreRequest;
use App\Http\Requests\Item\UpdateRequest;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Item::orderBy("id", "desc")->paginate(request('itemsPerPage') ?? 15);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        return Item::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $Item)
    {
        return $Item;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Item $Item)
    {
        $Item->update($request->validated());

        return $Item;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $Item)
    {
        $Item->delete();

        return response()->noContent();
    }
}
