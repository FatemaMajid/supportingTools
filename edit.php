<?php
require_once("checkLogin.php");
require_once("configuration.php");
// Make sure activity_id and activity_type_id are set in the URL

function uploadFileAndInsertPath($conn, $fileId, $fileKey, $isImage=false) {
    global $FileDir;
    if ($_FILES[$fileKey]['size'] > 0) {
        $fileName = $_FILES[$fileKey]['name'];
        $fileTmpName = $_FILES[$fileKey]['tmp_name'];
        $file_folder = $isImage ? 'images/' : 'files/';

        // Add random hexa number before the file name
        $random_hex = bin2hex(random_bytes(4));
        $file_folder .= $random_hex . '_' . $fileName;

        $allowed_types = $isImage ? ['jpg', 'png', 'bmp', 'pdf'] : ['jpg', 'png', 'bmp', 'pdf'];
        $file_extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $file_extension = strtolower($file_extension);
        if (!in_array($file_extension, $allowed_types)) {
            echo '<script>alert("Error: Only JPG, PNG, BMP, and PDF ' . ($isImage ? 'images' : 'files') . ' are allowed.");</script>';
            exit;
        }

        $check_query = $isImage ? "SELECT image_id FROM images WHERE image = ?" : "SELECT file_id FROM files WHERE file = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $file_folder);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $file_id = $check_result->fetch_assoc()[$isImage ? 'image_id' :'file_id'];
        } else {
            if (move_uploaded_file($fileTmpName, $FileDir . $file_folder)) {
                $sql = $isImage ? "INSERT INTO images (image) VALUES (?)" : "INSERT INTO files (file) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $file_folder);

                if ($stmt->execute()) {
                    // echo ($isImage ? "Image" : "File") . " path inserted successfully.";
                    $file_id = $conn->insert_id;
                    return $file_id;
                } else {
                    echo '<script>alert("Error inserting ' . ($isImage ? 'image' : 'file') . ' path: ' . addslashes($conn->error) . '");</script>';
                    exit;
                }
            } else {
                echo '<script>alert("Error uploading ' . ($isImage ? 'image' : 'file') . '.");</script>';
                exit;
            }
        }
    } else {
        // No new file uploaded, keep the old file ID
        $file_id = $fileId;
    }
    return $file_id;
}

if (isset($_REQUEST['activity_id']) && isset($_REQUEST['activity_type_id'])) {
    $activity_id = $_REQUEST['activity_id'];
    $activity_type_id = $_REQUEST['activity_type_id'];
    $cond = "";
    $stmt = "";
    if (!$_SESSION['isAdmin'])
        $cond = " AND l.lecturer_id=" . $_SESSION["lecturer_id"];

    // if (!isset($_POST['Update'])){
    // Prepare the query based on the activity_type_id
    if ($activity_type_id == 1 || $activity_type_id == 2 || $activity_type_id == 3 || $activity_type_id == 7 || $activity_type_id == 9 || $activity_type_id == 10 || $activity_type_id == 8) {
        $stmt = $conn->prepare('SELECT ai.activity_id, l.lecturer_name, a_t.activity_type, ai.activity_date, ai.activity_to_date, ai.activity_name, ai.activity_place, pt.participation_type ,f.file,f.file_id,i.image,i.image_id,s.sub_activity_type,s.sub_activity_id,note
                                    FROM activity_information ai
                                    LEFT JOIN lecturers l ON ai.lecturer_id = l.lecturer_id
                                    LEFT JOIN activity_type a_t ON ai.activity_type_id = a_t.activity_type_id
                                    LEFT JOIN participation pt ON ai.participation_type_id = pt.participation_type_id
                                    LEFT JOIN files f ON ai.file_id = f.file_id
                                    LEFT JOIN images i ON ai.image_id = i.image_id
                                    LEFT JOIN sub_activities s ON ai.sub_activity_id = s.sub_activity_id
                                    WHERE ai.activity_id = ?' . $cond);
    } elseif ($activity_type_id == 4) {
        $stmt = $conn->prepare('SELECT com.activity_id, l.lecturer_name, a_t.activity_type, com.activity_date, NULL AS activity_to_date, com.activity_name, com.activity_place, NULL AS participation_type, f.file,f.file_id,i.image,i.image_id,null,null,note
                                    FROM committees com
                                    LEFT JOIN lecturers l ON com.lecturer_id = l.lecturer_id
                                    LEFT JOIN activity_type a_t ON com.activity_type_id = a_t.activity_type_id
                                    LEFT JOIN files f ON com.file_id = f.file_id
                                    LEFT JOIN images i ON com.image_id = i.image_id
                                    WHERE com.activity_id = ?' . $cond);
    } elseif ($activity_type_id == 5) {
        $stmt = $conn->prepare('SELECT mis.mission_id AS activity_id, l.lecturer_name, a_t.activity_type, coun.country , mis.from_date AS activity_date, mis.to_date AS activity_to_date, mis.activity_name, mis.activity_place , f.file,f.file_id , i.image , i.image_id,null,null,note
                                    FROM missions mis
                                    LEFT JOIN lecturers l ON mis.lecturer_id = l.lecturer_id
                                    LEFT JOIN activity_type a_t ON mis.activity_type_id = a_t.activity_type_id
                                    LEFT JOIN countries coun ON mis.country_id = coun.country_id
                                    LEFT JOIN files f ON mis.file_id = f.file_id
                                    LEFT JOIN images i ON mis.image_id = i.image_id
                                    WHERE mis.mission_id = ?' . $cond);
    } elseif ($activity_type_id == 6) {
        $stmt = $conn->prepare('SELECT pro.promotion_id AS activity_id, l.lecturer_name, a_t.activity_type, act.acadimic_title, pro.university_order_date AS activity_date, pro.acadimic_title_order_date AS activity_to_date, pro.major, pro.specialty, NULL AS participation_type, a_t.activity_type_id, f1.file AS file1, f1.file_id AS file_id1, f2.file AS file2, f2.file_id AS file_id2, note
                                FROM promotions pro
                                LEFT JOIN lecturers l ON pro.lecturer_id = l.lecturer_id
                                LEFT JOIN activity_type a_t ON pro.activity_type_id = a_t.activity_type_id
                                LEFT JOIN files f1 ON pro.file_id = f1.file_id
                                LEFT JOIN files f2 ON pro.file_id2 = f2.file_id
                                LEFT JOIN acadimic_titles act ON pro.acadimic_title_id = act.acadimic_title_id
                                WHERE pro.promotion_id = ?' . $cond);
    }

    // Bind the parameter and execute the query
    $stmt->bind_param('i', $activity_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // $fileId=$row['file_id'];
        if ($activity_type_id == 1 || $activity_type_id == 2 || $activity_type_id == 3 || $activity_type_id == 7 || $activity_type_id == 10 || $activity_type_id == 9 || $activity_type_id == 8) {
            $lecturer_name = $row['lecturer_name'];
            $activity_type = $row['activity_type'];
            $activity_date = $row['activity_date'];
            $activity_to_date = $row['activity_to_date'];
            $activity_name = $row['activity_name'];
            $activity_place = $row['activity_place'];
            $participation_type = $row['participation_type'];
            $file =  $row['file'];
            $fileId = $row['file_id'];
            $image = $row['image'];
            $imageId = $row['image_id'];
            $sub_activity_type = $row['sub_activity_type'];
            $note = $row['note'];
        } elseif ($activity_type_id == 4) {
            $lecturer_name = $row['lecturer_name'];
            $activity_type = $row['activity_type'];
            $activity_date = $row['activity_date'];
            $activity_name = $row['activity_name'];
            $activity_place = $row['activity_place'];
            $file =  $row['file'];
            $fileId = $row['file_id'];
            $image = $row['image'];
            $imageId = $row['image_id'];
            $note = $row['note'];
        } elseif ($activity_type_id == 5) {
            $lecturer_name = $row['lecturer_name'];
            $activity_type = $row['activity_type'];
            $activity_date = $row['activity_date'];
            $activity_to_date = $row['activity_to_date'];
            $activity_name = $row['activity_name'];
            $activity_place = $row['activity_place'];
            $country = $row['country'];
            $file =  $row['file'];
            $fileId = $row['file_id'];
            $image = $row['image'];
            $imageId = $row['image_id'];
            $note = $row['note'];
        } elseif ($activity_type_id == 6) {
            $lecturer_name = $row['lecturer_name'];
            $activity_type = $row['activity_type'];
            $acadimic_title = $row['acadimic_title'];
            $acadimic_title_order_date = $row['activity_to_date'];
            $major = $row['major'];
            $specialty = $row['specialty'];
            $university_order_date = $row['activity_date'];
            $file1 = $row['file1'];
            $fileId = $row['file_id1'];
            $file2 = $row['file2'];
            $fileId2 = $row['file_id2'];
            $note = $row['note'];
        }
    } else {
        echo "No activity found with the provided ID.";
        exit;
    }
} else {
    echo "Activity ID or Activity Type ID not provided.";
    exit;
}


// update section //
// update section //

// update section //
//echo $uploaded_file_id;


if (isset($_POST['Update'])) {
   

    // معالجة تحديث السجل في قاعدة البيانات بناءً على نوع النشاط
    if ($activity_type_id == 1 || $activity_type_id == 2 || $activity_type_id == 3 || $activity_type_id == 7 || $activity_type_id == 8 || $activity_type_id == 9 || $activity_type_id == 10) {
        $activity_to_date = mysqli_real_escape_string($conn, $_POST['activity_to_date']);
        $activity_date = mysqli_real_escape_string($conn, $_POST['activity_date']);
        $activity_name = mysqli_real_escape_string($conn, $_POST['activity_name']);
        $activity_place = mysqli_real_escape_string($conn, $_POST['activity_place']);
        $note = mysqli_real_escape_string($conn, $_POST['note']);
        $file_id = uploadFileAndInsertPath($conn, $fileId, 'file');
        $image_id = uploadFileAndInsertPath($conn, $imageId, 'image', true);
        $sql = $conn->prepare('UPDATE activity_information SET activity_date=?, activity_to_date=?, activity_name=?, activity_place=?, file_id=?, image_id=?, note=? WHERE activity_id=?');
        $sql->bind_param("ssssiisi", $activity_date, $activity_to_date, $activity_name, $activity_place, $file_id, $image_id, $note, $activity_id);
    } elseif ($activity_type_id == 4) {
        $committeeDate = mysqli_real_escape_string($conn, $_POST['activity_date']);
        $activity_name = mysqli_real_escape_string($conn, $_POST['activity_name']);
        $activity_place = mysqli_real_escape_string($conn, $_POST['activity_place']);
        $note = mysqli_real_escape_string($conn, $_POST['note']);
        $file_id = uploadFileAndInsertPath($conn,$fileId, 'file');
        $image_id = uploadFileAndInsertPath($conn, $imageId, 'image', true);
        $sql = $conn->prepare('UPDATE committees SET activity_date=?, activity_name=?, activity_place=?,file_id=?,image_id=?, note=? WHERE activity_id=?');
        $sql->bind_param("sssiisi", $committeeDate, $activity_name, $activity_place, $file_id, $image_id, $note, $activity_id);
    } elseif ($activity_type_id == 5) {
        $activity_to_date = mysqli_real_escape_string($conn, $_POST['activity_to_date']);
        $activity_date = mysqli_real_escape_string($conn, $_POST['activity_date']);
        $activity_name = mysqli_real_escape_string($conn, $_POST['activity_name']);
        $activity_place = mysqli_real_escape_string($conn, $_POST['activity_place']);
        $note = mysqli_real_escape_string($conn, $_POST['note']);
        $file_id = uploadFileAndInsertPath($conn,$fileId, 'file');
        $image_id = uploadFileAndInsertPath($conn, $imageId, 'image', true);
        $sql = $conn->prepare('UPDATE missions SET from_date=?, to_date=?, activity_name=?, activity_place=?, file_id=?,image_id=?, note=? WHERE mission_id=?');
        $sql->bind_param("ssssiisi", $activity_date, $activity_to_date, $activity_name, $activity_place, $file_id, $image_id, $note, $activity_id);
    } elseif ($activity_type_id == 6) {
        // $acadimic_title = mysqli_real_escape_string($conn, $_POST['acadimic_title']);
        $acadimic_title_order_date = mysqli_real_escape_string($conn, $_POST['acadimic_title_order_date']);
        $university_order_date = mysqli_real_escape_string($conn, $_POST['university_order_date']);
        $major = mysqli_real_escape_string($conn, $_POST['major']);
        $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
        $note = mysqli_real_escape_string($conn, $_POST['note']);
        $file_id1 = uploadFileAndInsertPath($conn,$fileId, 'file');
        $file_id2 = uploadFileAndInsertPath($conn,$fileId2, 'file2');
        $sql = $conn->prepare('UPDATE promotions SET acadimic_title_order_date=?, university_order_date=?, major=?, specialty=?, file_id=?,file_id2=?, note=? WHERE promotion_id=?');
        $sql->bind_param("ssssiisi", $acadimic_title_order_date, $university_order_date, $major, $specialty, $file_id1, $file_id2, $note, $activity_id);
    } else {
        echo "Unexpected value of activity_type_id: " . $activity_type_id;
    }

    if ($sql->execute()) {
        echo "<script>
                    alert('تم التحديث بنجاح');
                    document.location='user_home.php'; 
                </script>";
    } else {
        echo "حدث خطأ اثناء التحديث: " . $conn->error;
        exit;
    }
}

?>



<h2>عرض التفاصيل</h2>
<form method="POST" action="edit.php" dir="rtl" id="activityForm" enctype="multipart/form-data">

    <label for="lecturer_name">اسم التدريسي : </label>
    <input type="text" id="lecturer_name" name="lecturer_name" value="<?php echo $lecturer_name; ?>" disabled><br>

    <label for="activity_type">نوع النشاط : </label>
    <input type="text" id="activity_type" name="activity_type" value="<?php echo $activity_type; ?>" disabled><br>
    <input type="hidden" id="activity_type_id" name="activity_type_id" value="<?php echo $activity_type_id; ?>"><br>
    <input type="hidden" id="activity_id" name="activity_id" value="<?php echo $activity_id; ?>">

    <!-- fields -->
    <?php
    if ($activity_type_id == 1 || $activity_type_id == 2 || $activity_type_id == 3 || $activity_type_id == 7 || $activity_type_id == 8 || $activity_type_id == 9 || $activity_type_id == 10) {
        echo '<input type="text" id="sub_activity_type" name="sub_activity_type" value="' . htmlspecialchars($sub_activity_type) . '" disabled><br>';
        echo '<label for="participation_type">نوع المشاركة : </label>';
        echo '<input type="text" id="participation_type" name="participation_type" value="' . $participation_type . '" disabled><br>';
        echo '<label for="activity_date">التاريخ : </label>';
        echo '<input type="date" id="activity_date" name="activity_date" value="' . $activity_date . '" disabled><br>';
        echo '<label for="activity_to_date">الى التاريخ : </label>';
        echo '<input type="date" id="activity_to_date" name="activity_to_date" value="' . $activity_to_date . '" disabled><br>';
        echo '<label for="file">الامر الاداري : </label>
    <div id="fileDisplay">';
        if ($fileId != null) {
            echo '<a href="files.php?file_id=' .  $fileId . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a><br>';
            // echo $row['file_id'];
            echo '<span id="span" onclick="removeFileOrImage(\'file\',  \'file_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        } else {
            echo 'لايوجد ملف.<br>';
            echo '<span id="span" onclick="addFileOrImage(\'file\', \'file_id\');" style="cursor: pointer; display: none;"> اضافة </span>';
        }
        echo '</div>
    <input type="file" id="file" name="file" style="display: none;">
    <input type="hidden" id="file_id" name="file_id" value="' . $fileId . '">';

        echo '<label for="image">الصورة : </label>
        <div id="imageDisplay">';
        if ($imageId!=null) {
            echo '<a href="images.php?image_id=' . $imageId . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a>';
            echo '<span id="spanImage" onclick="removeFileOrImage(\'image\',  \'image_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        }else {
            echo 'لايوجد صورة.<br>';
            echo '<span id="spanImage" onclick="addFileOrImage(\'image\', \'image_id\');" style="cursor: pointer; display: none;"> اضافة </span>';
        }
        echo '</div>
        <input type="file" id="image" name="image" style="display: none;">
        <input type="hidden" id="image_id" name="image_id" value="' . $imageId . '">';
    } elseif ($activity_type_id == 4) {
        echo '<input type="hidden" id="" name=""  disabled>';
        echo '<input type="hidden" id="" name=""  disabled>';
        echo '<label for="activity_date">التاريخ : </label>';
        echo '<input type="date" id="activity_date" name="activity_date" value="' . $activity_date . '" disabled><br>';
        echo '<label for="file">الامر الاداري : </label>
    <div id="fileDisplay">';
        if ($fileId != null) {
            echo '<a href="files.php?file_id=' .  $fileId . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a><br>';
            // echo $row['file_id'];
            echo '<span id="span" onclick="removeFileOrImage(\'file\',  \'file_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        } else {
            echo 'لايوجد ملف.<br>';
            echo '<span id="span" onclick="addFileOrImage(\'file\', \'file_id\');" style="cursor: pointer; display: none;"> اضافة </span>';
        }
        echo '</div>
    <input type="file" id="file" name="file" style="display: none;">
    <input type="hidden" id="file_id" name="file_id" value="' . $fileId . '">';

        echo '<label for="image">الصورة : </label>
        <div id="imageDisplay">';
        if ($imageId!=null) {
            echo '<a href="images.php?image_id=' . $imageId . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a>';
            echo '<span id="spanImage" onclick="removeFileOrImage(\'image\',  \'image_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        }else {
            echo 'لايوجد صورة.<br>';
            echo '<span id="spanImage" onclick="addFileOrImage(\'image\', \'image_id\');" style="cursor: pointer; display: none;"> اضافة </span>';
        }
        echo '</div>
        <input type="file" id="image" name="image" style="display: none;">
        <input type="hidden" id="image_id" name="image_id" value="' . $imageId . '">';
    } elseif ($activity_type_id == 5) {
        echo '<label for="country">الدولة : </label>';
        echo '<input type="text" id="country" name="country" value="' . $country . '"disabled ><br>';
        echo '<input type="hidden" id="" name=""  disabled>';
        echo '<label for="activity_date">التاريخ : </label>';
        echo '<input type="date" id="activity_date" name="activity_date" value="' . $activity_date . '" disabled><br>';
        echo '<label for="activity_to_date">الى التاريخ : </label>';
        echo '<input type="date" id="activity_to_date" name="activity_to_date" value="' . $activity_to_date . '" disabled><br>';
        echo '<label for="file">الامر الاداري : </label>
    <div id="fileDisplay">';
        if ($fileId != null) {
            echo '<a href="files.php?file_id=' .  $fileId . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a><br>';
            // echo $row['file_id'];
            echo '<span id="span" onclick="removeFileOrImage(\'file\',  \'file_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        } else {
            echo 'لايوجد ملف.<br>';
            echo '<span id="span" onclick="addFileOrImage(\'file\', \'file_id\');" style="cursor: pointer; display: none;"> اضافة </span>';
        }
        echo '</div>
    <input type="file" id="file" name="file" style="display: none;">
    <input type="hidden" id="file_id" name="file_id" value="' . $fileId . '">';

        echo '<label for="image">الصورة : </label>
        <div id="imageDisplay">';
        if ($imageId!=null) {
            echo '<a href="images.php?image_id=' . $imageId . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a>';
            echo '<span id="spanImage" onclick="removeFileOrImage(\'image\',  \'image_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        }else {
            echo 'لايوجد صورة.<br>';
            echo '<span id="spanImage" onclick="addFileOrImage(\'image\', \'image_id\');" style="cursor: pointer; display: none;"> اضافة </span>';
        }
        echo '</div>
        <input type="file" id="image" name="image" style="display: none;">
        <input type="hidden" id="image_id" name="image_id" value="' . $imageId . '">';
    } elseif ($activity_type_id == 6) {
        echo '<label for="acadimic_title">اللقب العلمي : </label>';
        echo '<input type="text" id="acadimic_title" name="acadimic_title" value="' . $acadimic_title . '" disabled><br>';
        echo '<input type="hidden" id="" name=""  disabled><br>';
        echo '<label for="university_order_date">تاريخ صدور الامر الجامعي : </label>';
        echo '<input type="date" id="university_order_date" name="university_order_date" value="' . $university_order_date . '" disabled><br>';
        echo '<label for="major">التخصص العام: </label>';
        echo '<input type="text" id="major" name="major" value="' . htmlspecialchars($major) . '"disabled ><br>';
        echo '<label for="specialty">التخصص الدقيق: </label>';
        echo '<input type="text" id="specialty" name="specialty" value="' . htmlspecialchars($specialty) . '" disabled><br>';
        echo '<label for="acadimic_title_order_date">تاريخ صدور اللقب العلمي : </label>';
        echo '<input type="date" id="acadimic_title_order_date" name="acadimic_title_order_date" value="' .htmlspecialchars($acadimic_title_order_date) . '" disabled><br>';
        echo '<label for="file">الامر الجامعي : </label>
            <div id="fileDisplay">';
        if ($file1) {
            echo '<a href="files.php?file_id=' .  $fileId . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a><br>';
            // echo $row['file_id'];
            echo '<span id="span" onclick="removeFileOrImage(\'file\', \'fileDisplay\', \'file_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        }
        echo '</div>
            <input type="file" id="file" name="file" style="display: none;">
            <input type="hidden" id="file_id" name="file_id" value="' . $fileId . '">';
            echo '<label for="file">الامر الاداري : </label>
            <div id="fileDisplay">';
        if ($file2) {
            echo '<a href="files.php?file_id=' .  $fileId2 . '" target="_blank"><i class="fa fa-file-pdf-o"></i> عرض</a><br>';
            // echo $row['file_id'];
            echo '<span id="span2" onclick="removeFileOrImage(\'file2\', \'fileDisplay\', \'file_id\');" style="cursor: pointer; display: none;"> تغيير </span>';
        }
        echo '</div>
            <input type="file" id="file2" name="file2" style="display: none;">
            <input type="hidden" id="file_id2" name="file_id2" value="' . $fileId2 . '">';
    }
    ?>

    <?php if ($activity_type_id != 6) { ?>
        <label for="activity_name">اسم النشاط : </label>
        <input type="text" id="activity_name" name="activity_name" value="<?php echo $activity_name; ?>" disabled><br>

        <label for="activity_place">مكان النشاط : </label>
        <input type="text" id="activity_place" name="activity_place" value="<?php echo $activity_place; ?>" disabled><br>
    <?php } ?>
    <label for="note">الملاحظات : </label>
<textarea id="note" name="note"  style="resize: both; width:100%" disabled><?php echo htmlspecialchars($note); ?></textarea><br>


    <input type="submit" name="Update" value="تحديث" style="display: none;" id="updateButton">
    <button type="button" id="editButton">تعديل</button>
    <button type="button"><a href="javascript:$('#detailsModal').modal('hide');">اغلاق</a></button>
</form>

<script>
    var activityTypeId= document.getElementById("activity_type_id").value;

    document.getElementById("editButton").addEventListener("click", function() {
        var form = document.getElementById("activityForm");
        var fields = form.elements;
        for (var i = 6; i < fields.length; i++) {
            fields[i].disabled = false;
        }
        
        document.getElementById("editButton").style.display = "none";
        document.getElementById("updateButton").style.display = "inline-block";
        document.getElementById("span").style.display = "inline-block";

        if(activityTypeId!=6){
            document.getElementById("spanImage").style.display = "inline-block";
        }else{
          document.getElementById("span2").style.display = "inline-block";
        }
    });

    if(activityTypeId==1 || activityTypeId==2 || activityTypeId==3 || activityTypeId==7 || activityTypeId==8 || activityTypeId==9 || activityTypeId==10){
        var subActivityTypeField = document.getElementById('sub_activity_type');
        if (subActivityTypeField.value === '') {
            subActivityTypeField.style.display = 'none';
        }
    }

    function removeFileOrImage(type,hiddenInput) {
        if (confirm("هل انت متأكد من التغيير؟")) {
            document.getElementById(hiddenInput).value = "";
            document.getElementById(type).style.display = "inline-block";
        }
    }

    function addFileOrImage(type, hiddenInput) {
        document.getElementById(type).style.display = "inline-block";
    }


    //     $(document).ready(function () {
    //     $('#updateButton').click(function() {
    //         var formData = $('#activityForm').serialize(); 
    //         $.ajax({
    //             url: 'edit.php', 
    //             type: 'POST',
    //             data: formData,
    //             success: function(response) {
    //                 alert('Update successful');
    //                 $('#detailsModal').modal('hide');
    //                 location.reload(); 
    //             },
    //             error: function(xhr, status, error) {
    //                 alert('Error updating record: ' + xhr.responseText);
    //             },
    //             complete: function() {
    //                 $('#updateButton').prop('disabled', true); 
    //             }
    //         });
    //     });
    // });
</script>