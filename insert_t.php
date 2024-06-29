<?php
$forAdmin = true;
require_once("checkLogin.php");
require_once("configuration.php");

$initialActivityTypeId = ['activity_type_id' => null]; // Default value

function executeQuery($conn, $query, $params = null)
{
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param(...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}




function insertData($conn, $tableName, $columns, $values, $types, $params, $unrequiredFields, $activityTypeId)
{

    foreach ($unrequiredFields as $field) {
        if (empty($params[$field])) {
            continue;
        }
        return false;
    }

    // Check if record already exist
    if ($activityTypeId == 3 || $activityTypeId == 2 || $activityTypeId == 1 || $activityTypeId == 7 || $activityTypeId == 8 || $activityTypeId == 9 || $activityTypeId == 10) {
        $check_query = "SELECT * FROM $tableName WHERE lecturer_id = ? AND activity_type_id = ? AND (activity_name = ? OR activity_date=?)";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iiss", $params[0], $params[1], $params[4], $params[3]);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    } else if ($activityTypeId == 4) {
        $check_query = "SELECT * FROM $tableName WHERE lecturer_id = ? AND activity_type_id = ? AND (activity_name = ? OR activity_date=?) ";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iiss", $params[0], $params[1], $params[2], $params[3]);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    } else if ($activityTypeId == 5) {
        $check_query = "SELECT * FROM $tableName WHERE lecturer_id = ? AND activity_type_id = ? AND  (activity_name = ? OR from_date=?) ";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iiss", $params[0], $params[1], $params[5], $params[3]);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    } elseif ($activityTypeId == 6) {
        $check_query = "SELECT * FROM $tableName WHERE (lecturer_id = ? AND acadimic_title_id = ?) OR ( lecturer_id = ? AND acadimic_title_order_date = ?)";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iiis", $params[0], $params[2], $params[0], $params[6]);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    }
    // echo '<script>alert("Parameters: ' . implode(', ', $params) . '");</script>';

    if ($check_result->num_rows > 0) {
        echo '<script>alert("هذا النشاط مدخل مسبقا");';
        echo 'window.location.href = window.location.href;</script>';
        // exit;
        return false;
    } else {
        $sql = $conn->prepare("INSERT INTO $tableName ($columns) VALUES ($values)");
        $sql->bind_param($types, ...$params);
        return $sql->execute();
    }
}




function uploadFile($fileKey, $File_ID, $conn, $isImage = false)
{
    global $FileDir;
    if ($File_ID == 0) {
        if (isset($_FILES[$fileKey]) && !empty($_FILES[$fileKey]['tmp_name'])) {
            $file_name = $_FILES[$fileKey]['name'];
            $file_tmp = $_FILES[$fileKey]['tmp_name'];
            $file_folder = $isImage ? 'images/' : 'files/';

            // Add random hexa number before the file name
            $random_hex = bin2hex(random_bytes(4));
            $file_folder .= $random_hex . '_' . $file_name;

            $allowed_types = $isImage ? ['jpg', 'png', 'bmp', 'pdf'] : ['jpg', 'png', 'bmp', 'pdf'];
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
            if (!in_array($file_extension, $allowed_types)) {
                echo 'Error: Only JPG, PNG, BMP, and PDF ' . ($isImage ? 'images' : 'files') . ' are allowed.';
                exit;
            }

            $check_query = $isImage ? "SELECT image_id FROM images WHERE image = ?" : "SELECT file_id FROM files WHERE file = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $file_folder);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $file_id = $check_result->fetch_assoc()[$isImage ? 'image_id' : 'file_id'];
            } else {
                if (move_uploaded_file($file_tmp, $FileDir . $file_folder)) {
                    $insert_query = $isImage ? "INSERT INTO images (image) VALUES (?)" : "INSERT INTO files (file) VALUES (?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("s", $file_folder);
                    $insert_stmt->execute();
                    $file_id = $conn->insert_id;
                } else {
                    echo 'Error uploading ' . ($isImage ? 'image' : 'file') . '.';
                    exit;
                }
            }
            return $file_id;
        }
    } else {
        return $File_ID;
    }
    return null;
}






try {

    $activityTypes = executeQuery($conn, 'SELECT * FROM activity_type');
    $lecturers = executeQuery($conn, 'SELECT * FROM lecturers WHERE deleted=0');
    $participations = executeQuery($conn, 'SELECT * FROM participation');
    $activityNames = executeQuery($conn, 'SELECT DISTINCT activity_name FROM activity_information');
    $countries = executeQuery($conn, 'SELECT * FROM countries');
    $Titles = executeQuery($conn, 'SELECT * FROM acadimic_titles');

    if (isset($_POST['insert'])) {
        // Extract form data
        $activityTypeId = isset($_POST['activity_type_id']) ? (int)$_POST['activity_type_id'] : 0;

        $lecturerId = isset($_POST['lecturer_id']) ? (int)$_POST['lecturer_id'] : 0;
        // $File_ID=(int)$_POST['file_id'];
        $File_ID = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;
        $file_id = uploadFile('file', $File_ID, $conn);
        $note = mysqli_real_escape_string($conn, $_POST['note']);

        // Define table name, columns, and values
        $tableName = '';
        $columns = '';
        $values = '';
        $types = '';
        $unrequiredFields = [];

        if ($activityTypeId == 3 || $activityTypeId == 2 || $activityTypeId == 1 || $activityTypeId == 7 || $activityTypeId == 8 || $activityTypeId == 9 || $activityTypeId == 10) {
            // Extract additional form data for specific activity types
            $participationTypeId = (int)$_POST['participation_type_id'];
            $activityDate1 = mysqli_real_escape_string($conn, $_POST['date']);
            $activityName = mysqli_real_escape_string($conn, $_POST['activity_name']);
            $activityDate2 = mysqli_real_escape_string($conn, $_POST['date2']);
            $activityPlace = mysqli_real_escape_string($conn, $_POST['activity_place']);
            $Image_ID = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
            $image_id = uploadFile('image', $Image_ID, $conn, true);
            $subActivityTypeId = isset($_POST['sub_activity_id']) ? (int)$_POST['sub_activity_id'] : null;

            $currentTime = date('Y-m-d');
            if ($activityDate1 > $currentTime || $activityDate2 > $currentTime) {
                echo '<script>alert("يرجى ادخال تاريخ لا يتجاوز التاريخ الحالي");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }
            // Check if sub_activity_id is zero or NULL
            if ($subActivityTypeId == 0) {
                $subActivityTypeId = 5;
            }

            // Define required fields
            $requiredFields = [
                'lecturer_id' => $lecturerId,
                'activity_type_id' => $activityTypeId,
                'activity_date' => $activityDate1,
                'activity_name' => $activityName,
                'activity_place' => $activityPlace,
                'participation_type_id' => $participationTypeId,
                'file_id' => $file_id,
                'sub_activity_id' => ($subActivityTypeId == 0 ? null : $subActivityTypeId)
                // 'sub_activity_id' =>$subActivityTypeId
            ];


            // Check if any required field is empty
            $emptyFields = [];
            foreach ($requiredFields as $field => $value) {
                if (empty($value)) {
                    $emptyFields[] = $field;
                }
            }

            // Skip insertion and display error message if any required field is empty
            if (!empty($emptyFields)) {
                //  header("location:insert_t.php");
                echo '<script>alert("يرجى ادخال كافة المعلومات المطلوبة");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }

            // Define table and column names
            $tableName = 'activity_information';
            $columns = 'lecturer_id, activity_type_id, activity_date, activity_to_date, activity_name, activity_place, participation_type_id, file_id, image_id,sub_activity_id,note';
            $values = '?, ?, ?, ?, ?, ?, ?, ?, ?,?,?';
            $types = 'iissssiiiis';
            $unrequiredFields = ['activity_to_date', 'image_id', 'note'];

            $activityData = [$lecturerId, $activityTypeId, $activityDate1, $activityDate2, $activityName, $activityPlace, $participationTypeId, $file_id, $image_id, $subActivityTypeId, $note];
        } else if ($activityTypeId == 4) {
            $committeeDate = mysqli_real_escape_string($conn, $_POST['date']);
            $activityName = mysqli_real_escape_string($conn, $_POST['activity_name']);
            $activityPlace = mysqli_real_escape_string($conn, $_POST['activity_place']);
            $Image_ID = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
            $image_id = uploadFile('image', $Image_ID, $conn, true);

            $currentTime = date('Y-m-d');
            if ($committeeDate > $currentTime) {
                echo '<script>alert("يرجى ادخال تاريخ لا يتجاوز التاريخ الحالي");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }
            // Define required fields
            $requiredFields = [
                'lecturer_id' => $lecturerId,
                'activity_type_id' => $activityTypeId,
                'activity_name' => $activityName,
                'activity_date' => $committeeDate,
                'activity_place' => $activityPlace,
                'file_id' => $file_id,
                // 'note'=> $note
            ];

            // Check if any required field is empty
            $emptyFields = [];
            foreach ($requiredFields as $field => $value) {
                if (empty($value)) {
                    $emptyFields[] = $field;
                }
            }

            // Skip insertion and display error message if any required field is empty
            if (!empty($emptyFields)) {
                //  header("location:insert_t.php");
                echo '<script>alert("يرجى ادخال كافة المعلومات المطلوبة");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }

            // Define table and column names
            $tableName = 'committees';
            $columns = 'lecturer_id, activity_type_id, activity_name, activity_date	, activity_place, file_id, image_id,note';
            $values = '?, ?, ?, ?, ?, ?, ?,?';
            $types = 'iisssiis';
            $unrequiredFields = ['image_id', 'note'];

            $activityData = [$lecturerId, $activityTypeId, $activityName, $committeeDate, $activityPlace, $file_id, $image_id, $note];
        } else if ($activityTypeId == 5) {
            // Handle mission activity type
            // Extract additional form data
            $countryID = (int)$_POST['country_id'];
            $activityDate1 = mysqli_real_escape_string($conn, $_POST['date']);
            $activityName = mysqli_real_escape_string($conn, $_POST['activity_name']);
            $activityDate2 = mysqli_real_escape_string($conn, $_POST['date2']);
            $activityPlace = mysqli_real_escape_string($conn, $_POST['activity_place']);
            $Image_ID = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
            $image_id = uploadFile('image', $Image_ID, $conn, true);

            $currentTime = date('Y-m-d');
            if ($activityDate1 > $currentTime || $activityDate2 > $currentTime) {
                echo '<script>alert("يرجى ادخال تاريخ لا يتجاوز التاريخ الحالي");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }
            // Define required fields
            $requiredFields = [
                'lecturer_id' => $lecturerId,
                'activity_type_id' => $activityTypeId,
                'country_id' => $countryID,
                'from_date' => $activityDate1,
                'activity_name' => $activityName,
                'activity_place' => $activityPlace,
                'file_id' => $file_id,
                // 'note'=> $note
            ];

            // Check if any required field is empty
            $emptyFields = [];
            foreach ($requiredFields as $field => $value) {
                if (empty($value)) {
                    $emptyFields[] = $field;
                }
            }

            // Skip insertion and display error message if any required field is empty
            if (!empty($emptyFields)) {
                //  header("location:insert_t.php");
                echo '<script>alert("يرجى ادخال كافة المعلومات المطلوبة");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }

            // Define table and column names
            $tableName = 'missions';
            $columns = 'lecturer_id, activity_type_id, country_id, from_date, to_date, activity_name, activity_place, file_id, image_id,note';
            $values = '?, ?, ?, ?, ?, ?, ?, ?, ?,?';
            $types = 'iisssssiis';
            $unrequiredFields = ['image_id', 'note'];

            $activityData = [$lecturerId, $activityTypeId, $countryID, $activityDate1, $activityDate2, $activityName, $activityPlace, $file_id, $image_id, $note];
        } else if ($activityTypeId == 6) {

            $academicTitleId = (int)$_POST['acadimic_title_id'];
            $universityOrderDate = mysqli_real_escape_string($conn, $_POST['university_order_date']);
            $academicTitleDate = mysqli_real_escape_string($conn, $_POST['acadimic_title_order_date']);
            $major = mysqli_real_escape_string($conn, $_POST['major']);
            $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
            $file_id2 = isset($_POST['file_id2']) ? (int)$_POST['file_id2'] : 0;
            $file_id2 = uploadFile('file2', $file_id2, $conn);

            $currentTime = date('Y-m-d');
            if ($universityOrderDate > $currentTime || $academicTitleDate > $currentTime) {
                echo '<script>alert("يرجى ادخال تاريخ لا يتجاوز التاريخ الحالي");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }
            $requiredFields = [
                'lecturer_id' => $lecturerId,
                'activity_type_id' => $activityTypeId,
                'acadimic_title_id' => $academicTitleId,
                'university_order_date' => $universityOrderDate,
                'acadimic_title_order_date' => $academicTitleDate,
                'major' => $major,
                'specialty' => $specialty,
                'file_id' => $file_id,
                'file_id2' => $file_id2,
                // 'note'=> $note
            ];


            $emptyFields = [];
            foreach ($requiredFields as $field => $value) {
                if (empty($value)) {
                    $emptyFields[] = $field;
                }
            }

            if (!empty($emptyFields)) {
                echo '<script>alert("يرجى ادخال كافة المعلومات المطلوبة");';
                echo 'window.location.href = window.location.href;</script>';
                exit;
            }

            $tableName = 'promotions';
            $columns = 'lecturer_id, activity_type_id, acadimic_title_id, university_order_date, major, specialty, acadimic_title_order_date, file_id, file_id2,note';
            $values = '?, ?, ?, ?, ?, ?, ?, ?, ?,?';
            $types = 'iiissssiis';
            $unrequiredFields = ['note'];

            $activityData = [$lecturerId, $activityTypeId, $academicTitleId, $universityOrderDate, $major, $specialty, $academicTitleDate, $file_id, $file_id2, $note];
        }


        // Perform insertion
        if (insertData($conn, $tableName, $columns, $values, $types, $activityData, $unrequiredFields, $activityTypeId)) {
            echo "<script>
                    alert('تمت عملية الاضافة بشكل صحيح');
                </script>";
        } else {
            echo 'Error: ' . $conn->error;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
//echo"llllllllllllllll,$sql";



?>
<!DOCTYPE html>
<html lang="en" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>



    <title>insert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #e6f3ff;
        }

        .container {
            display: flex;
            justify-content: space-between;
        }

        .form_container {
            flex: 1;
            margin-right: 10px;
        }

        .suggested-activities {
            width: 30%;
            max-height: 20%;
            padding: 10px;
            margin-right: 10px;

            top: 20px;
            left: 20px;
            background-color: #aaddbb7a;
            color: black;
            display: none;
            overflow-y: scroll;
            overflow-x: hidden;
        }

        form input,
        form select {
            width: calc(100% - 22px);
            padding: 10px;
            margin: 15px 0;
            border: 1px solid #5e8e7e;
            border-radius: 5px;
            box-sizing: border-box;
        }

        form label {
            margin-top: 10px;
            font-weight: bold;
            color: #5e8e7e;
        }

        form .insert {
            background-color: #00796b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }


        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown-content a {
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropbtn {
            background-color: #00796b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        @media only screen and (max-width: 375px) {
            .form_container {
                width: 100%;
                max-width: none;
            }

            .suggested-activities {
                width: 90%;
                max-width: none;
                margin-right: auto;
                margin-left: auto;
                left: auto;
                position: fixed;
                background-color: #adb;

            }

            .dropdown-content {
                min-width: 120px;
            }
        }
    </style>

</head>

<body>
    <?php include "header.php" ?>
    <h2>اضافة الى جدول المعلومات :</h2>
    <div class="container">
        <div class="form_container">
            <form method="POST" id="myform" enctype="multipart/form-data">
                <span style="color:red;font-size:x-large;"> * </span><label for="activity_type">اختر نوع النشاط :</label>
                <input type="hidden" name="activity_type_id" id="activity_type_id">
                <input list="activity_types" name="activity_type" id="activity_type" autocomplete="off">
                <datalist id="activity_types">
                    <?php

                    while ($row = $activityTypes->fetch_assoc()) {
                        echo "<option data-value='{$row['activity_type_id']}' value='{$row['activity_type']}'>";
                    }
                    ?>
                </datalist><br>
                <input type="hidden" name="sub_activity_id" id="sub_activity_id">
                <input list="sub_activity_types" name="sub_activity_type" id="sub_activity_type" autocomplete="off" style="display: none;">
                <datalist id="sub_activity_types">
                </datalist><br>

                <div id="dynamicFields">
                    <?php
                    require_once("getFields.php");
                    dynamicFields($initialActivityTypeId['activity_type_id'], $lecturers, $participations, $activityNames, $countries, $Titles);
                    ?>
                </div>

                <button type="button" id="insert"> اضافة </button>
                <input type="hidden" name="insert" value="insert">
                <button type="button"><a href="home.php">الغاء</a></button>
            </form>
        </div>
        <div id="suggested_activities" class="suggested-activities"></div>

    </div>

    <?php include "footer.php" ?>
    <script>
        function removeFileOrImage(elementId, displayElementId, inputId) {
            var fileOrImageElement = document.getElementById(elementId);
            var fileOrImageDisplayElement = document.getElementById(displayElementId);
            fileOrImageDisplayElement.innerHTML = "";
            fileOrImageElement.style.display = "block";

            // Reset the input file value
            var inputElement = document.getElementById(inputId);
            inputElement.value = "";
        }

        function fillFields(activityDate, activityName, activityPlace, file, fileId, image, imageId) {
            console.log('Filling fields:', activityDate, activityName, activityPlace, file, fileId, image, imageId);
            var dateElement = document.getElementById('date');
            var nameElement = document.getElementById('activity_name');
            var placeElement = document.getElementById('activity_place');
            var fileElement = document.getElementById('file');
            var fileElement_exist = document.getElementById('imgfile');
            var imageElement = document.getElementById('image');
            var imageElement_exist = document.getElementById('imagefile');

            console.log('Date :', activityDate);
            console.log('Name :', activityName);
            console.log('Place :', activityPlace);
            console.log('File :', file);
            console.log('File id :', fileId);
            console.log('image :', image);
            console.log('image id :', imageId);

            if (dateElement && nameElement && placeElement && fileElement && imageElement) {
                dateElement.value = activityDate;
                nameElement.value = activityName;
                placeElement.value = activityPlace;
                fileElement_exist.innerHTML = `<span id="removefile" onclick="removeFileOrImage('file', 'imgfile', 'file_id');" style="cursor: pointer;"> X </span><a href="files.php?file_id=${fileId}" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض </a>` +
                    `<input type="hidden" value="${fileId}" name="file_id" id="file_id">`;

                fileElement.style.display = "none";
                imageElement_exist.innerHTML = `<span id="removeimage" onclick="removeFileOrImage('image', 'imagefile', 'image_id');" style="cursor: pointer;"> X </span><a href="images.php?image_id=${imageId}" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض </a>` +
                    `<input type="hidden" value="${imageId}" name="image_id" id="image_id">`;

                imageElement.style.display = "none";
            } else {
                console.error('One or more elements not found.');
            }
        }

        function fillLecturers(major, specialty) {
            console.log('Filling :', major, specialty);
            var majorElement = document.getElementById('major');
            var specialtyElement = document.getElementById('specialty');


            console.log('major :', majorElement);
            console.log('specialty :', specialtyElement);

            if (majorElement && specialtyElement) {
                majorElement.value = major;
                specialtyElement.value = specialty;

            } else {
                console.error('One or more elements not found.');
            }
        }


        function get_datalist_value(inputListId, dataList) {
            var selected_activity_type = $(inputListId).val();

            var activity_type_option = $(dataList + ' option').filter(function() {
                return $(this).val() === selected_activity_type;
            });
            return $(activity_type_option).data('value');
        }

        $(document).ready(function() {

            // Function to handle dynamic field changes based on activity_type
            function updateDynamicFields() {
                document.getElementById('suggested_activities').innerHTML = "";
                document.getElementById("suggested_activities").style.display = "none";
                document.getElementById("sub_activity_type").style.display = "none";
                $('#activity_type_id').val(get_datalist_value('#activity_type', '#activity_types'));
                var activityType = $('#activity_type').val();
                var activityTypeId = $('#activity_type_id').val();

                // AJAX request to get dynamic fields for the selected activity_type
                $.ajax({
                    type: 'POST',
                    url: 'getFields.php',
                    data: {
                        activity_type_id: activityTypeId
                    },
                    success: function(response) {
                        $('#dynamicFields').html(response);
                        $('#date').change(onDateChange);
                        $('#activity_name').on('input', onActivityNameChange);
                        $('#lecturer_name').on('input', onLecturerChange);

                    }
                });
                // $('#sub_activity_types').html("<option data-value='1' value='hhhh'>"
                //     +"<option data-value='2' value='gggg'>"
                // );
                // document.getElementById("sub_activity_type").style.display = "inline-block";
                $.ajax({
                    type: 'POST',
                    url: 'getSubActivities.php',
                    data: {
                        activity_type_id: activityTypeId
                    },
                    success: function(response) {
                        if (response != "") {
                            $('#sub_activity_types').html(response);
                            document.getElementById("sub_activity_type").style.display = "inline-block";
                        } else {
                            document.getElementById("sub_activity_id").value = "";
                        }
                    }
                });
            }

            $('#insert').on('click', function(e) {

                $('#activity_type_id').val(get_datalist_value('#activity_type', '#activity_types'));
                $('#lecturer_id').val(get_datalist_value('#lecturer_name', '#lecturerList'));
                $('#participation_type_id').val(get_datalist_value('#participation_type', '#participationTypes'));
                $('#country_id').val(get_datalist_value('#country', '#countryList'));
                $('#acadimic_title_id').val(get_datalist_value('#acadimic_title', '#titlesList'));
                $('#sub_activity_id').val(get_datalist_value('#sub_activity_type', '#sub_activity_types'));

                $('#myform').submit();
            });

            // Event listener for activity_type change
            $('#activity_type').on('change', function() {
                updateDynamicFields();
            });

            // Initial call to populate dynamic fields based on default activity_type
            updateDynamicFields();


            // suggestions 
            document.getElementById('activity_type').addEventListener('change', function() {
                var selectedOption = document.querySelector('#activity_types option[value="' + this.value + '"]');
                if (selectedOption) {
                    document.getElementById('activity_type_id').value = selectedOption.getAttribute('data-value');
                }
            });

            async function getActivities(parameter, value) {
                const activityTypeId = document.getElementById('activity_type_id').value;
                console.log('Activity Type ID:', activityTypeId);

                const url = `get_activities.php?${parameter}=${value}&activity_type_id=${activityTypeId}`;

                try {
                    const response = await fetch(url);
                    const responseData = await response.json();

                    let suggestionsHTML = '';
                    responseData.forEach(activity => {
                        suggestionsHTML += `<li onclick='fillFields("${activity.activity_date}", "${activity.activity_name}", "${activity.activity_place}", "${activity.file}", "${activity.file_id}" , "${activity.image}", "${activity.image_id}")'>
                                            ${activity.activity_name} - ${activity.activity_place} - ${activity.activity_date} - <a href="files.php?file_id=${activity.file_id}" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض الملف</a> -
                                             <a href="images.php?image_id=${activity.image_id}" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض الصورة</a>
                                        </li><br>`;
                    });
                    if (suggestionsHTML != "") {
                        suggestionsHTML = "<p>يمكنك الضغط على الخيارات : </p><br>" + "<ul>" + suggestionsHTML + '</ul>';

                        document.getElementById('suggested_activities').innerHTML = suggestionsHTML;
                        document.getElementById("suggested_activities").style.display = "block";
                    }
                } catch (error) {
                    console.error('Error fetching suggestions:', error);
                }
            }

            async function getLecturreInfo() {
                const lecturerId = document.getElementById('lecturer_id').value;
                console.log('Lecturer ID:', lecturerId);
                const activityTypeId = document.getElementById('activity_type_id').value;
                console.log('Activity Type ID:', activityTypeId);

                const url = `lecturer_info.php?lecturer_id=${lecturerId}&activity_type_id=${activityTypeId}`;
                console.log(url);
                try {
                    const response = await fetch(url);
                    const responseData = await response.json();

                    let lecturerS = '';
                    responseData.forEach(LecturerInfo => {
                        if (LecturerInfo != "")
                            lecturerS += `<li onclick='fillLecturers("${LecturerInfo.major}", "${LecturerInfo.specialty}")'>
                                            ${LecturerInfo.major} - ${LecturerInfo.specialty} 
                                        </li><br>`;
                    });
                    console.log(lecturerS);
                    if (lecturerS != '') {
                        lecturerS = "<p>يمكنك الضغط على الخيارات : </p><br>" + "<ul>" + lecturerS + '</ul>';

                        document.getElementById('suggested_activities').innerHTML = lecturerS;
                        document.getElementById("suggested_activities").style.display = "block";
                    }
                } catch (error) {
                    console.error('Error fetching suggestions:', error);
                }
            }

            function onDateChange() {
                const date = document.getElementById('date').value;
                console.log('Date changed1:', date);

                getActivities('date', date);
            }

            function onActivityNameChange() {
                const activityName = document.getElementById('activity_name').value;
                getActivities('activity_name', activityName);
            }

            function onLecturerChange() {
                // const Lecturer = document.getElementById('lecturer_name').value;
                // console.log('Lecturer :', Lecturer);
                $('#lecturer_id').val(get_datalist_value('#lecturer_name', '#lecturerList'));
                getLecturreInfo();
            }

            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('date').addEventListener('change', onDateChange);
                document.getElementById('activity_name').addEventListener('input', onActivityNameChange);
            });

        });
    </script>

</body>

</html>