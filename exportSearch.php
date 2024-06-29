<?php
require_once("checkLogin.php");
require_once("configuration.php");
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$search_lec_Name = isset($_GET['search_lec_Name']) ? '%' . $_GET['search_lec_Name'] . '%' : '';
$search_act_type = isset($_GET['search_act_type']) ? '%' . $_GET['search_act_type'] . '%' : '';
$search_dt1 = isset($_GET['search_dt1']) ? '%' . $_GET['search_dt1'] . '%' : '';
$search_dt2 = isset($_GET['search_dt2']) ? '%' . $_GET['search_dt2'] . '%' : '';

    $sqlQuery = "SELECT activity_id, lecturer_name, acadimic_title,activity_type,sub_activity_type, activity_date, activity_to_date,university_order_date,acadimic_title_order_date, activity_name,major,specialty, activity_place, participation_type ,file , image FROM (
                    SELECT ai.activity_id, l.lecturer_name,null as acadimic_title, a_t.activity_type,st.sub_activity_type, ai.activity_date,ai.activity_to_date,null as university_order_date,  null as acadimic_title_order_date,  ai.activity_name,null as major, null as specialty, ai.activity_place, pt.participation_type, f.file ,i.image
            FROM activity_information ai
            LEFT JOIN lecturers l ON ai.lecturer_id = l.lecturer_id
            LEFT JOIN activity_type a_t ON ai.activity_type_id = a_t.activity_type_id
            LEFT JOIN sub_activities st ON ai.sub_activity_id = st.sub_activity_id
            LEFT JOIN participation pt ON ai.participation_type_id = pt.participation_type_id
            LEFT JOIN files f ON ai.file_id = f.file_id
            LEFT JOIN images i ON ai.image_id = i.image_id
            UNION
            SELECT com.activity_id, l.lecturer_name,null as acadimic_title, a_t.activity_type,null as sub_activity_type, com.activity_date, NULL AS activity_to_date,null as university_order_date ,null as acadimic_title_order_date,  com.activity_name,null as major, null as specialty, com.activity_place, NULL AS participation_type, f.file ,i.image
            FROM committees com
            LEFT JOIN lecturers l ON com.lecturer_id = l.lecturer_id
            LEFT JOIN activity_type a_t ON com.activity_type_id = a_t.activity_type_id
            LEFT JOIN files f ON com.file_id = f.file_id
            LEFT JOIN images i ON com.image_id = i.image_id
            UNION
            SELECT mis.mission_id AS activity_id, l.lecturer_name,null as acadimic_title, a_t.activity_type,null as sub_activity_type, mis.from_date AS activity_date, mis.to_date AS activity_to_date,null as university_order_date,null as acadimic_title_order_date, mis.activity_name,null as major, null as specialty, mis.activity_place, NULL AS participation_type, f.file ,i.image
            FROM missions mis
            LEFT JOIN lecturers l ON mis.lecturer_id = l.lecturer_id
            LEFT JOIN activity_type a_t ON mis.activity_type_id = a_t.activity_type_id
            LEFT JOIN files f ON mis.file_id = f.file_id
            LEFT JOIN images i ON mis.image_id = i.image_id
            UNION
            SELECT pro.promotion_id AS activity_id , l.lecturer_name, act.acadimic_title, a_t.activity_type,null as sub_activity_type, null AS activity_date ,null AS activity_to_date, pro.university_order_date, pro.acadimic_title_order_date,pro.activity_name, pro.major, pro.specialty ,null as activity_place, NULL AS participation_type, f.file , NULL AS image
            FROM promotions pro
            LEFT JOIN lecturers l ON pro.lecturer_id = l.lecturer_id
            LEFT JOIN activity_type a_t ON pro.activity_type_id = a_t.activity_type_id
            LEFT JOIN files f ON pro.file_id = f.file_id AND pro.file_id2 = f.file_id
            LEFT JOIN acadimic_titles act ON pro.acadimic_title_id = act.acadimic_title_id
                ) AS combined_activities";

$params = [];
$types = '';

if (!empty($search_lec_Name) || !empty($search_act_type) || (!empty($_GET['search_dt1']) && !empty($_GET['search_dt2']))) {
    $sqlQuery .= " WHERE ";
    $conditions = array();
    if (!empty($search_lec_Name)) {
        $conditions[] = "lecturer_name LIKE ?";
        $params[] = $search_lec_Name;
        $types .= 's';
    }
    if (!empty($search_act_type)) {
        $conditions[] = "activity_type LIKE ?";
        $params[] = $search_act_type;
        $types .= 's';
    }
    if (!empty($_GET['search_dt1']) && !empty($_GET['search_dt2'])) {
        $conditions[] = "activity_date BETWEEN ? AND ?";
        $params[] = $_GET['search_dt1'];
        $params[] = $_GET['search_dt2'];
        $types .= 'ss';
    } elseif (!empty($_GET['search_dt1'])) {
        $conditions[] = "activity_date = ?";
        $params[] = $_GET['search_dt1'];
        $types .= 's';
    }
    $sqlQuery .= implode(" AND ", $conditions);
}


if (!empty($params)) {
    $sql = $conn->prepare($sqlQuery);
    if ($sql) {
        $sql->bind_param($types, ...$params);
        $sql->execute();
        $result = $sql->get_result();
    } else {
        // Handle prepare error
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        exit;
    }
} else {
    $result = $conn->query($sqlQuery);
}

if ($result->num_rows > 0) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'اسم التدريسي');
    $sheet->setCellValue('B1', 'اللقب العلمي');
    $sheet->setCellValue('C1', ' التخصص العام');
    $sheet->setCellValue('D1', 'التخصص الدقيق ');
    $sheet->setCellValue('E1', 'نوع النشاط');
    $sheet->setCellValue('F1', ' النوع الفرعي');
    $sheet->setCellValue('G1', 'من التاريخ');
    $sheet->setCellValue('H1', 'الى التاريخ');
    $sheet->setCellValue('I1', ' تاريخ اصدار الامر الجامعي');
    $sheet->setCellValue('J1', ' تاريخ اصدار الامر الاداري');
    $sheet->setCellValue('K1', 'اسم النشاط');
    $sheet->setCellValue('L1', 'مكان النشاط');
    $sheet->setCellValue('M1', 'نوع المشاركة');
    $sheet->setCellValue('N1', 'الامر الاداري');
    $sheet->setCellValue('O1', 'الصورة');

    $activities = [];
    while ($row_data = $result->fetch_assoc()) {
        $activities[$row_data['activity_type']][] = $row_data;
    }

    foreach ($activities as $activityType => $activityData) {
        $activityType = preg_replace('/[^\p{L}\p{N}\s]/u', '', $activityType);
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle($activityType);

        $headerRow = ['اسم التدريسي','اللقب العلمي','التخصص العام','التخصص الدقيق', 'نوع النشاط','النوع الفرعي', 'تاريخ النشاط', 'تاريخ الانتهاء من النشاط','تاريخ اصدار الامر الجامعي','تاريخ اصدار اللقب العلمي', 'اسم النشاط', 'مكان النشاط', 'نوع المشاركة', 'الامر الاداري', 'الصورة'];
        $worksheet->fromArray([$headerRow], null, 'A1');

        $row = 2;
        foreach ($activityData as $rowData) {
            $worksheet->setCellValue('A' . $row, $rowData['lecturer_name']);
            $worksheet->setCellValue('B' . $row, $rowData['acadimic_title']);
            $worksheet->setCellValue('C' . $row, $rowData['major']);
            $worksheet->setCellValue('D' . $row, $rowData['specialty']);
            $worksheet->setCellValue('E' . $row, $rowData['activity_type']);
            $worksheet->setCellValue('F' . $row, $rowData['sub_activity_type']);
            $worksheet->setCellValue('G' . $row, $rowData['activity_date']);
            $worksheet->setCellValue('H' . $row, $rowData['activity_to_date']);
            $worksheet->setCellValue('I' . $row, $rowData['university_order_date']);
            $worksheet->setCellValue('J' . $row, $rowData['acadimic_title_order_date']);
            $worksheet->setCellValue('K' . $row, $rowData['activity_name']);
            $worksheet->setCellValue('L' . $row, $rowData['activity_place']);
            $worksheet->setCellValue('M' . $row, $rowData['participation_type']);
            $worksheet->setCellValue('N' . $row, $rowData['file']);
            if ($rowData['file'] != "")
                $worksheet->getCell('N' . $row)->getHyperlink()->setUrl($rowData['file']);
            $worksheet->setCellValue('O' . $row, $rowData['image']);
            if ($rowData['image'] != "")
                $worksheet->getCell('O' . $row)->getHyperlink()->setUrl($rowData['file']);
            $row++;
        }
    }
    $spreadsheet->removeSheetByIndex(0);
    // Create a new sheet for all activities
    $allActivitiesSheet = $spreadsheet->createSheet();
    $allActivitiesSheet->setTitle("كل المعلومات");

    $headerRow = ['اسم التدريسي','اللقب العلمي','التخصص العام','التخصص الدقيق', 'نوع النشاط','النوع الفرعي', 'تاريخ النشاط', 'تاريخ الانتهاء من النشاط','تاريخ اصدار الامر الجامعي','تاريخ اصدار اللقب العلمي', 'اسم النشاط', 'مكان النشاط', 'نوع المشاركة', 'الامر الاداري', 'الصورة'];
    $allActivitiesSheet->fromArray([$headerRow], null, 'A1');

    $row = 2;
    foreach ($activities as $activityType => $activityData) {

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
            if($rowData['image']!="")
                $allActivitiesSheet->getCell('O' . $row)->getHyperlink()->setUrl($rowData['file']);
            $row++;
        }
    }

    $writer = new Xlsx($spreadsheet);
    $tempFileName = tempnam(sys_get_temp_dir(), 'exported_search_file');
    $writer->save($tempFileName);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="exported_search_file.xlsx"');
    header('Cache-Control: max-age=0');
    readfile($tempFileName);
    unlink($tempFileName);
    exit;
} else {
    echo "No records found.";
    exit;
}
