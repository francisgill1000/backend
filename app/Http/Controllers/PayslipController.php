<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PayslipController extends Controller
{
    public function index($department_id, request $request)
    {

        //$request = $request->all();
        $employees = Employee::where("department_id", $department_id)
            ->withOut("schedule")
            ->with("payroll", "designation")
            ->where("company_id", $request->company_id)
            ->get(["id", "employee_id", "display_name", "first_name", "last_name"]);
        $data = [];

        foreach ($employees as $employee) {
            $singleEmployee = $this->renderPayslip($employee, $request);
            $data[] = $singleEmployee;

            $this->renderPdf($singleEmployee, $request);
        }

        return $data;
    }
    public function generateWithDepartmentId(request $request)
    {

        //$request = $request->all();
        $employees = Employee::withOut("schedule")
            ->with("payroll", "designation")
            ->where("company_id", $request->company_id)
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            })
            ->get(["id", "employee_id", "display_name", "first_name", "last_name"]);
        $data = [];

        foreach ($employees as $employee) {

            $singleEmployee = $this->renderPayslip($employee, $request);
            $data[] = $singleEmployee;

            $this->renderPdf($singleEmployee, $request);
        }

        return $data;
    }
    public function generateWithCompanyIds($company_Id)
    {
        $request = [
            "year" => date('Y'),
            "month" => date('m') - 1,
            "company_id" => $company_Id,
        ];
        $employees = Employee::withOut("schedule")
            ->with("payroll", "designation")

            ->where("company_id", $request["company_id"])
            ->get(["id", "employee_id", "display_name", "first_name", "last_name"]);
        $data = [];

        foreach ($employees as $employee) {

            $singleEmployee = $this->renderPayslip($employee, $request);

            $data[] = $singleEmployee;

            $this->renderPdf($singleEmployee, $request);
        }

        return $data;
    }
    public function generateWithEmployeeids(request $request)
    {

        // $request = $request->all();

        $employees = Employee::withOut("schedule")
            ->with("payroll", "designation")
            ->wherein("id", $request["employee_ids"])
            ->where("company_id", $request["company_id"])
            ->get(["id", "employee_id", "display_name", "first_name", "last_name"]);

        $data = [];
        foreach ($employees as $employee) {
            $singleEmployee = [];
            try {
                $singleEmployee = $this->renderPayslip($employee, $request);

                $this->renderPdf($singleEmployee, $request);

                if ($this->getPayslipstatus($request["company_id"], $employee->employee_id, $request['month'], $request['year'])) {
                    $singleEmployee['status'] = true;
                    $singleEmployee['status_message'] = $employee->employee_id . ': ' . $employee->first_name . ' ' . $employee->last_name . " - Payslip Generated Successfully";
                } else {
                    $singleEmployee['status'] = false;
                    $singleEmployee['status_message'] = $employee->employee_id . ': ' . $employee->first_name . ' ' . $employee->last_name . " - Salary Details are not available";
                }
            } catch (\Throwable $th) {
                //throw $th;

                $singleEmployee['status'] = false;
                $singleEmployee['status_message'] = $employee->employee_id . ': ' . $employee->first_name . ' ' . $employee->last_name . " -  Salary Details are not available";
            }

            $data[] = $singleEmployee;
        }

        return $data;
    }
    public function getPayslipstatus($company_id, $employee_id, $month, $year)
    {
        $pdfFile_name = 'payslips/' . $company_id . '/' . $company_id . '_' . $employee_id . '_' . $month . '_' . $year . '_payslip.pdf';
        if (Storage::disk('local')->exists($pdfFile_name)) {
            return true;
        } else {
            return false;
        }
    }
    public function downloadAllPayslipszip(request $request)
    {

        // $request = $request->all();

        $company_Id = $request->company_id;
        $month = $request->month;
        $year = $request->year;
        $employee_ids = explode(',', $request->employee_ids);

        $zip_file = 'payslips_' . $month . '_' . $year . ' .zip'; // Name of our archive to download
        $results = [];
        // Initializing PHP class
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($employee_ids as $employee_id) {
            $invoice_file = 'app/payslips/' . $company_Id . '/' . $company_Id . '_' . $employee_id . '_' . $month . '_' . $year . '_payslip.pdf';
            $invoice_file1 = 'payslips/' . $company_Id . '/' . $company_Id . '_' . $employee_id . '_' . $month . '_' . $year . '_payslip.pdf';

            if (Storage::disk('local')->exists($invoice_file1)) {

                $results[] = $invoice_file;
                // Adding file: second parameter is what will the path inside of the archive
                // So it will create another folder called "storage/" inside ZIP, and put the file there.
                $zip->addFile(storage_path($invoice_file), $invoice_file);
            }
        }

        $zip->close();

        // We return the file immediately after download
        return response()->download($zip_file);
    }
    public function renderPayslip($employee, $request)
    {

        if (!$employee->payroll) {

            $payroll['status'] = false;
            $payroll['status_message'] = $employee->employee_id . ': ' . $employee->display_name . " - Salary Details are not available";
        }
        $payroll = [];

        try {
            //code...
            $company_id = $employee->payroll->company_id;
            $employee_id = $employee->employee_id;
            $month = $request['month']; // date('m');
            $dateObj = DateTime::createFromFormat('!m', $month);
            $monthName = $dateObj->format('F'); // March

            $year = $request['year']; //date('Y');
            $attendances = Attendance::where(["company_id" => $company_id, "employee_id" => $employee_id])
                ->whereMonth('date', '=', $month)
                ->whereIn('status', ['P', 'A'])
                ->get();

            $present = $attendances->where('status', 'P')->count();
            $absent = $attendances->where('status', 'A')->count();
            // $present = 29;
            // $absent = 1;
            $payroll = $employee->payroll;
            $payroll->payslip_number = "#" . $employee_id . (int) date("m") - 1 . (int) date("y");
            $salary_type = $payroll->payroll_formula->salary_type;
            $payroll->salary_type = ucwords(str_replace("_", " ", $salary_type));

            $salary_type = $payroll->payroll_formula->salary_type;
            $payroll->SELECTEDSALARY = $salary_type == "basic_salary" ? $payroll->basic_salary : $payroll->net_salary;

            $payroll->perDaySalary = $this->getPerDaySalary($payroll->SELECTEDSALARY ?? 0);
            $payroll->perHourSalary = $this->getPerHourSalary($payroll->perDaySalary ?? 0);

            $payroll->earnedSalary = $present * $payroll->perDaySalary;
            $payroll->deductedSalary = $absent * $payroll->perDaySalary;
            $payroll->earningsCount = $payroll->net_salary - $payroll->basic_salary;

            $extraEarnings = [
                "label" => "Basic",
                "value" => $payroll->basic_salary,
            ];

            $payroll->earnings = array_merge([$extraEarnings], $payroll->earnings);

            $payroll->deductions = [
                [
                    "label" => "Abents",
                    "value" => $payroll->deductedSalary,
                ],
            ];

            $payroll->earnedSubTotal = ($payroll->earningsCount) + ($payroll->earnedSalary);
            $payroll->salary_and_earnings = ($payroll->earningsCount) + ($payroll->SELECTEDSALARY);

            $payroll->finalSalary = ($payroll->salary_and_earnings) - $payroll->deductedSalary;

            $payroll->monthName = $monthName;
            $payroll->month = $month;
            $payroll->year = $year;
            $payroll->date = date('j F Y');
            $payroll->presentDays = $present;
            $payroll->absentDays = $absent;
            $payroll['employee_id'] = $employee->employee_id;
            $payroll['display_name'] = $employee->display_name;
            $payroll['first_name'] = $employee->first_name;
            $payroll['last_name'] = $employee->last_name;
            $payroll['position'] = $employee->designation->name;

            $payroll['status'] = true;
            $payroll['status_message'] = $employee->employee_id . ': ' . $employee->display_name . " - Payslip Generated Successfully";
            //company details

            $payroll->company = Company::where('id', $employee->payroll->company_id)->first();
        } catch (\Throwable $th) {
            //throw $th;
            $payroll['status'] = false;
            $payroll['status_message'] = $employee->employee_id . ': ' . $employee->display_name . " - Salary Details are not available";
        }
        return $payroll;
    }

    public function show(Request $request, $id)
    {
        // return $this->generateWithEmployeeids($request);
        //code...

        $Payroll = Payroll::where(["employee_id" => $id])->with(["company", "employee:id,employee_id,system_user_id,display_name,first_name,last_name"])->first(["basic_salary", "net_salary", "earnings", "employee_id", "company_id"]);
        $Payroll->payslip_number = "#" . $id . (int) date("m") - 1 . (int) date("y");

        $salary_type = $Payroll->payroll_formula->salary_type;

        $Payroll->salary_type = ucwords(str_replace("_", " ", $salary_type));
        $Payroll->date = date('j F Y');

        $Payroll->SELECTEDSALARY = $salary_type == "basic_salary" ? $Payroll->basic_salary : $Payroll->net_salary;

        $Payroll->perDaySalary = $this->getPerDaySalary($Payroll->SELECTEDSALARY ?? 0);
        $Payroll->perHourSalary = $this->getPerHourSalary($Payroll->perDaySalary ?? 0);

        $conditions = ["company_id" => $request->company_id, "employee_id" => $Payroll->employee->system_user_id];

        $attendances = Attendance::where($conditions)
            ->whereMonth('date', '=', $request->month <= 9 ? "0" . $request->month : date("m"))
            ->whereIn('status', ['P', 'A', 'M', 'O'])
            ->get();

        $Payroll->present = $attendances->whereIn('status', 'P')->count();
        $Payroll->absent = $attendances->where('status', 'A')->count();
        $Payroll->missing = $attendances->where('status', 'M')->count();
        $Payroll->off = $attendances->where('status', 'O')->count();


        $Payroll->earnedSalary = ($Payroll->present + $Payroll->off) * $Payroll->perDaySalary;
        $Payroll->deductedSalary = $Payroll->absent * $Payroll->perDaySalary;
        $Payroll->earningsCount = $Payroll->net_salary - $Payroll->basic_salary;

        $extraEarnings = [
            "label" => "Basic",
            "value" => $Payroll->SELECTEDSALARY,
        ];
        $Payroll->earnings = array_merge([$extraEarnings], $Payroll->earnings);

        $Payroll->deductions = [
            [
                "label" => "Abents",
                "value" => $Payroll->deductedSalary,
            ],
        ];

        $Payroll->earnedSubTotal = ($Payroll->earningsCount) + ($Payroll->earnedSalary);
        $Payroll->salary_and_earnings = ($Payroll->earningsCount) + ($Payroll->SELECTEDSALARY);

        $Payroll->finalSalary = ($Payroll->salary_and_earnings) - $Payroll->deductedSalary;

        return $Payroll;
    }

    public function getPerHourSalary($perDaySalary)
    {
        return number_format($perDaySalary / 8, 2);
    }
    public function getPerDaySalary($salary)
    {
        return number_format($salary / 30, 2);
    }

    public function renderFakeData($company_id, $id)
    {
        $arr = [
            [
                "date" => date("Y-m-01"),
                "status" => "P",
                "employee_id" => $id,
                "company_id" => $company_id,
            ],
            [
                "date" => date("Y-m-02"),
                "status" => "P",
                "employee_id" => $id,
                "company_id" => $company_id,
            ],
            [
                "date" => date("Y-m-03"),
                "status" => "A",
                "employee_id" => $id,
                "company_id" => $company_id,
            ],
            [
                "date" => date("Y-m-04"),
                "status" => "P",
                "employee_id" => $id,
                "company_id" => $company_id,
            ],

        ];

        return Attendance::insert($arr);
    }

    public function renderPdf($data, $request)
    {

        if ($data && isset($data['employee_id'])) {

            $pdf = Pdf::loadView('pdf.payslip', compact("data"))->output();

            $pdfFile_name = 'payslips/' . $request["company_id"] . '/' . $request["company_id"] . '_' . $data->employee_id . '_' . $request["month"] . '_' . $request["year"] . '_payslip.pdf';
            Storage::disk('local')->put($pdfFile_name, $pdf);

            return $data;
        } else {
            return $data;
        }
    }

    public function downloadPayslipPdf(request $request)
    {
        $pdfFile_name = 'payslips/' . $request["company_id"] . '/' . $request["company_id"] . '_' . $request["employee_id"] . '_' . $request["month"] . '_' . $request["year"] . '_payslip.pdf';

        return Storage::download($pdfFile_name);
    }

    public function renderPayslipByEmployee(Request $request)
    {
        $data = $this->show($request, $request->employee_id);
        $data->month = date('F', mktime(0, 0, 0, $request->month, 1));
        $data->year = $request->year;
        return Pdf::loadView('pdf.payslip', compact('data'))->download();
    }
}
