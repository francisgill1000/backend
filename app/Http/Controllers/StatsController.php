<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreRequest;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index()
    {

        $expenseToday = Expense::whereDate('date', now()->toDateString())->sum('amount');
        $incomeToday = Income::whereDate('date', now()->toDateString())->sum('amount');

        $expenseToday = 50;
        $incomeToday = 10;

        $expenseWeekly = Expense::whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount');
        $incomeWeekly = Income::whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount');

        $expenseMonthly = Expense::whereMonth('date', now()->month)->sum('amount');
        $incomeMonthly = Income::whereMonth('date', now()->month)->sum('amount');

        $jsonData = [
            [
                'title' => 'Today',
                'data' => [
                    ['label' => 'Expense', 'value' => $expenseToday],
                    ['label' => 'Income', 'value' => $incomeToday],
                    ['label' => 'Profit/Loss', 'value' => $incomeToday - $expenseToday],
                ],
            ],
            [
                'title' => 'Weekly',
                'data' => [
                    ['label' => 'Expense', 'value' => $expenseWeekly],
                    ['label' => 'Income', 'value' => $incomeWeekly],
                    ['label' => 'Profit/Loss', 'value' => $incomeWeekly - $expenseWeekly],
                ],
            ],
            [
                'title' => 'Monthly',
                'data' => [
                    ['label' => 'Expense', 'value' => $expenseMonthly],
                    ['label' => 'Income', 'value' => $incomeMonthly],
                    ['label' => 'Profit/Loss', 'value' => $incomeMonthly - $expenseMonthly],
                ],
            ],
        ];

        return response()->json($jsonData);
    }
}
