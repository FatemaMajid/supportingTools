<?php
 require_once("checkLogin.php");
 require_once("configuration.php");
//require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


function check() {
    if ($_SESSION['isAdmin']) {
        return "";
    } else {
        return "WHERE l.lecturer_id = " . $_SESSION["lecturer_id"];
    }
}

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();

    $sqlQuery = "
                SELECT ai.activity_id, l.lecturer_name, null as acadimic_title, a_t.activity_type,st.sub_activity_type, ai.activity_date, ai.activity_to_date, null as university_order_date, null as acadimic_title_order_date, ai.activity_name, null as major, null as specialty, ai.activity_place, pt.participation_type, f.file ,i.image
                FROM activity_information ai
                LEFT JOIN lecturers l ON ai.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON ai.activity_type_id = a_t.activity_type_id
                LEFT JOIN sub_activities st ON ai.sub_activity_id = st.sub_activity_id
                LEFT JOIN participation pt ON ai.participation_type_id = pt.participation_type_id
                LEFT JOIN files f ON ai.file_id = f.file_id
                LEFT JOIN images i ON ai.image_id = i.image_id
                " . check() . "
                UNION
                SELECT com.activity_id, l.lecturer_name, null as acadimic_title, a_t.activity_type,null as sub_activity_type, com.activity_date, NULL AS activity_to_date, null as university_order_date ,null as acadimic_title_order_date,  com.activity_name, null as major, null as specialty, com.activity_place, NULL AS participation_type, f.file ,i.image
                FROM committees com
                LEFT JOIN lecturers l ON com.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON com.activity_type_id = a_t.activity_type_id
                LEFT JOIN files f ON com.file_id = f.file_id
                LEFT JOIN images i ON com.image_id = i.image_id
                " . check() . "
                UNION
                SELECT mis.mission_id AS activity_id, l.lecturer_name, null as acadimic_title, a_t.activity_type,null as sub_activity_type, mis.from_date AS activity_date, mis.to_date AS activity_to_date,null as university_order_date,null as acadimic_title_order_date, mis.activity_name,null as major, null as specialty, mis.activity_place, NULL AS participation_type, f.file ,i.image
                FROM missions mis
                LEFT JOIN lecturers l ON mis.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON mis.activity_type_id = a_t.activity_type_id
                LEFT JOIN files f ON mis.file_id = f.file_id
                LEFT JOIN images i ON mis.image_id = i.image_id
                " . check() . "
                UNION
                SELECT pro.promotion_id AS activity_id , l.lecturer_name, act.acadimic_title, a_t.activity_type,null as sub_activity_type, null AS activity_date ,null AS activity_to_date, pro.university_order_date, pro.acadimic_title_order_date,pro.activity_name, pro.major, pro.specialty ,null as activity_place, NULL AS participation_type, f.file , NULL AS image
                FROM promotions pro
                LEFT JOIN lecturers l ON pro.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON pro.activity_type_id = a_t.activity_type_id
                LEFT JOIN files f ON pro.file_id = f.file_id AND pro.file_id2 = f.file_id
                LEFT JOIN acadimic_titles act ON pro.acadimic_title_id = act.acadimic_title_id
                " . check() . "
                ";


// Execute the query
$result = $conn->query($sqlQuery);


if ($result->num_rows > 0) {
    
    // Group records by activity type
    $activityTypes = [];
    while ($row = $result->fetch_assoc()) {
        $activityType = $row['activity_type'];
        if (!isset($activityTypes[$activityType])) {
            $activityTypes[$activityType] = [];
        }
        $activityTypes[$activityType][] = $row;
    }

    

    // Create a new sheet for each activity type
    foreach ($activityTypes as $activityType => $records) {
       // Sanitize the activity type to remove invalid characters
        $activityType = preg_replace('/[^\p{L}\p{N}\s]/u', '', $activityType);

        // Create a new sheet for each activity type
        $sheetIndex = $spreadsheet->getSheetCount();
        $sheet = $spreadsheet->createSheet($sheetIndex);
        $sheet->setTitle($activityType);

        // Set the header row
        $headerRow = ['اسم التدريسي','اللقب العلمي','التخصص العام','التخصص الدقيق', 'نوع النشاط','النوع الفرعي', 'تاريخ النشاط', 'تاريخ الانتهاء من النشاط','تاريخ اصدار الامر الجامعي','تاريخ اصدار اللقب العلمي', 'اسم النشاط', 'مكان النشاط', 'نوع المشاركة', 'الامر الاداري', 'الصورة'];
        $sheet->fromArray([$headerRow], null, 'A1');

        // Add data rows
        $row = 2;
        foreach ($records as $record) {
            $sheet->setCellValue('A' . $row, $record['lecturer_name']);
            $sheet->setCellValue('B' . $row, $record['acadimic_title']);
            $sheet->setCellValue('C' . $row, $record['major']);
            $sheet->setCellValue('D' . $row, $record['specialty']);
            $sheet->setCellValue('E' . $row, $record['activity_type']);
            $sheet->setCellValue('F' . $row, $record['sub_activity_type']);
            $sheet->setCellValue('G' . $row, $record['activity_date']);
            $sheet->setCellValue('H' . $row, $record['activity_to_date']);
            $sheet->setCellValue('I' . $row, $record['university_order_date']);
            $sheet->setCellValue('J' . $row, $record['acadimic_title_order_date']);
            $sheet->setCellValue('K' . $row, $record['activity_name']);
            $sheet->setCellValue('L' . $row, $record['activity_place']);
            $sheet->setCellValue('M' . $row, $record['participation_type']);
            $sheet->setCellValue('N' . $row, $record['file']);
            if($record['file']!="")
                $sheet->getCell('N' . $row)->getHyperlink()->setUrl($record['file']);
            $sheet->setCellValue('O' . $row, $record['image']);
            if (!is_null($record['image']) && $record['image'] !== "")
            $sheet->getCell('O' . $row)->getHyperlink()->setUrl($record['image']);
            $row++;
        }
    }
    $spreadsheet->removeSheetByIndex(0);
    // Create a new sheet for all activities
$allActivitiesSheet = $spreadsheet->createSheet();
$allActivitiesSheet->setTitle("كل المعلومات");

$headerRow = ['اسم التدريسي','اللقب العلمي','التخصص العام','التخصص الدقيق', 'نوع النشاط','النوع الفرعي', 'تاريخ النشاط', 'تاريخ الانتهاء من النشاط','تاريخ اصدار الامر الجامعي','تاريخ اصدار اللقب العلمي', 'اسم النشاط', 'مكان النشاط', 'نوع المشاركة', 'الملف', 'الصورة'];
$allActivitiesSheet->fromArray([$headerRow], null, 'A1');

$row = 2;
foreach ($activityTypes as $activityType => $activityData) {
     
    foreach ($activityData as $rowData) {
        $allActivitiesSheet->setCellValue('A' . $row, $rowData['lecturer_name']);
        $allActivitiesSheet->setCellValue('B' . $row, $rowData['acadimic_title']);
        $allActivitiesSheet->setCellValue('C' . $row, $rowData['major']);
        $allActivitiesSheet->setCellValue('D' . $row, $rowData['specialty']);
        $allActivitiesSheet->setCellValue('E' . $row, $rowData['activity_type']);
        $allActivitiesSheet->setCellValue('F' . $row, $rowData['sub_activity_type']);
        $allActivitiesSheet->setCellValue('G' . $row, $rowData['activity_date']);
        $allActivitiesSheet->setCellValue('H' . $row, $rowData['activity_to_date']);
        $allActivitiesSheet->setCellValue('I' . $row, $rowData['university_order_date']);
        $allActivitiesSheet->setCellValue('J' . $row, $rowData['acadimic_title_order_date']);
        $allActivitiesSheet->setCellValue('K' . $row, $rowData['activity_name']);
        $allActivitiesSheet->setCellValue('L' . $row, $rowData['activity_place']);
        $allActivitiesSheet->setCellValue('M' . $row, $rowData['participation_type']);
        $allActivitiesSheet->setCellValue('N' . $row, $rowData['file']);
        if($rowData['file']!="")
            $allActivitiesSheet->getCell('N' . $row)->getHyperlink()->setUrl($rowData['file']);
        $allActivitiesSheet->setCellValue('O' . $row, $rowData['image']);
        if (!is_null($rowData['image']) && $rowData['image'] !== "")
                $allActivitiesSheet->getCell('O' . $row)->getHyperlink()->setUrl($rowData['image']);
        $row++;
    }
}

    // Save the spreadsheet and download the file
    $writer = new Xlsx($spreadsheet);
    $tempFileName = tempnam(sys_get_temp_dir(), 'exported_search_file');
    $writer->save($tempFileName);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="exported_file.xlsx"');
    header('Cache-Control: max-age=0');
    readfile($tempFileName);
    unlink($tempFileName);
    exit;
} else {
    echo "No records found.";
    exit;
}
?>
