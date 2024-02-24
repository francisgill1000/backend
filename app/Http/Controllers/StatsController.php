<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreRequest;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function getTodayStats()
    {

        $expenseToday = Expense::whereDate('date', now()->toDateString())->where("user_id", request("user_id") ?? 0)->sum('amount');
        $incomeToday = Income::whereDate('date', now()->toDateString())->where("user_id", request("user_id") ?? 0)->sum('amount');


        // if user occupation is (salaried persion)
        $incomeToday = Income::whereMonth('date', now()->month)->where("user_id", request("user_id") ?? 0)->sum('amount');


        $jsonData = [
            [
                'title' => 'Today',
                'data' => [
                    ['label' => 'Expense', 'value' => $expenseToday],
                    ['label' => 'Income', 'value' => $incomeToday],
                    ['label' => 'Profit/Loss', 'value' => $incomeToday - $expenseToday],
                ],
            ],
        ];

        return response()->json($jsonData);
    }

    public function getWeeklyStats()
    {
        $expenseWeekly = Expense::whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])->where("user_id", request("user_id") ?? 0)->sum('amount');
        $incomeWeekly = Income::whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])->where("user_id", request("user_id") ?? 0)->sum('amount');


        // if user occupation is (salaried persion)
        $incomeWeekly = Income::whereMonth('date', now()->month)->where("user_id", request("user_id") ?? 0)->sum('amount');


        $jsonData = [
            [
                'title' => 'Weekly',
                'data' => [
                    ['label' => 'Expense', 'value' => $expenseWeekly],
                    ['label' => 'Income', 'value' => $incomeWeekly],
                    ['label' => 'Profit/Loss', 'value' => $incomeWeekly - $expenseWeekly],
                ],
            ],
        ];

        return response()->json($jsonData);
    }

    public function getMonthlyStats()
    {
        $expenseMonthly = Expense::whereMonth('date', now()->month)->where("user_id", request("user_id") ?? 0)->sum('amount');
        $incomeMonthly = Income::whereMonth('date', now()->month)->where("user_id", request("user_id") ?? 0)->sum('amount');

        $jsonData = [
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
