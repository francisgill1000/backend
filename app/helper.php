<?php

use Illuminate\Support\Facades\File;

function removeFile($path, $file_name)
{
    $delete = public_path($path . $file_name);
    if (File::isFile($delete)) {
        unlink($delete);
    }
}

function saveFile($request, $destination, $attribute_name = null, $prefix = "", $sufix = "", $imageObj = null, $return_ext = false)
{
    if (isset($imageObj) && !empty($imageObj) && $attribute_name == null) {
        $temp = $imageObj;
        $file = $imageObj->getClientOriginalName();
        $file_ext = $imageObj->getClientOriginalExtension();
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        $image = ((!empty($prefix)) ? (str_ireplace(" ", "-", $prefix) . "-") : "") . str_ireplace(" ", "-", $fileName) . ((!empty($sufix)) ? "-" . str_ireplace(" ", "-", $sufix) : "") . "." . $file_ext;
        $temp->move($destination, $image);
    } else if (isset($attribute_name) && $request->hasFile($attribute_name) && $attribute_name != null) {
        $temp = $request->file($attribute_name);
        $file = $request->$attribute_name->getClientOriginalName();
        $file_ext = $request->$attribute_name->getClientOriginalExtension();
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        $image = ((!empty($prefix)) ? (str_ireplace(" ", "-", $prefix) . "-") : "") . str_ireplace(" ", "-", $fileName) . ((!empty($sufix)) ? "-" . str_ireplace(" ", "-", $sufix) : "") . "." . $file_ext;
        $temp->move($destination, $image);
    }

    if ($return_ext) {
        return ["name" => (isset($image)) ? $image : null, "ext" => (isset($file_ext)) ? $file_ext : null];
    }
    return (isset($image)) ? $image : null;
}


function ld($arr)
{
    echo "<pre>";
    echo json_encode($arr, JSON_PRETTY_PRINT);
}

function defaultCards($id = 1)
{
    return [
        "page" => "dashboard1",
        "type" => "card",
        "company_id" =>  $id,
        "style" => [
            [
                "title" => "Total Employee",
                "value" => "employeeCount",
                "color" => "#9C27B0",
                "icon" => "mdi mdi-account",
                "cols" => "12",
                "sm" => "6",
                "md" => "2",
                "calculated_value" => "09"
            ],
            [
                "title" => "Present",
                "value" => "presentCount",
                "color" => "#512DA8FF",
                "icon" => "mdi mdi-account",
                "cols" => "12",
                "sm" => "6",
                "md" => "2",
                "calculated_value" => "00"
            ],
            [
                "title" => "Absent",
                "value" => "absentCount",
                "color" => "#BF360CFF",
                "icon" => "mdi mdi-account",
                "cols" => "12",
                "sm" => "6",
                "md" => "2",
                "calculated_value" => "00"
            ],
            [
                "title" => "Late",
                "value" => "missingCount",
                "color" => "#263238FF",
                "icon" => "mdi mdi-account",
                "cols" => "12",
                "sm" => "6",
                "md" => "2",
                "calculated_value" => "00"
            ],
            [
                "title" => "Leave",
                "value" => "leaveCount",
                "color" => "#78909CFF",
                "icon" => "mdi mdi-account",
                "cols" => "12",
                "sm" => "6",
                "md" => "2",
                "calculated_value" => "00"
            ],
            [
                "title" => "Vacation",
                "value" => "vacationCount",
                "color" => "#558B2FFF",
                "icon" => "mdi mdi-account",
                "cols" => "12",
                "sm" => "6",
                "md" => "2",
                "calculated_value" => "00"
            ]
        ]
    ];
}


function defaultRoles($id = 1)
{
    return [
        [
            "name" => "Employee",
            "role_type" => "employee",
            "company_id" => $id,
        ],
        [
            "name" => "Manager",
            "role_type" => "employee",
            "company_id" => $id,
        ],
    ];
}


function defaultDepartments($id = 1)
{

    return [
        [
            "name" => "Accounts",
            "company_id" => $id,
        ],
        [
            "name" => "Admin",
            "company_id" => $id,
        ],
        [
            "name" => "It Dep",
            "company_id" => $id,
        ],
        [
            "name" => "Sales",
            "company_id" => $id,
        ]
    ];
}
