<?php
require_once("checkLogin.php");
require_once("configuration.php");

if (!function_exists('executeQuery')) {
    function executeQuery($conn, $query, $params = null) {
        $stmt = $conn->prepare($query);
        if($params) {
            $stmt->bind_param(...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
}

function dynamicFields($activityTypeId, $lecturers, $participations, $activityNames, $countries,$Titles){
    ob_start();
    ?>
    <span style="color:red;font-size:x-large;"> * </span><label for="lecturer_name">اسم التدريسي :</label>
    <input type="hidden" name="lecturer_id" id="lecturer_id" <?php  if(!$_SESSION['isAdmin']){
                                                                echo 'value="'.$_SESSION['lecturer_id'].'" ';
                                                                } 
                                                                ?>>
    <input list="lecturerList" name="lecturer_name" id="lecturer_name"  autocomplete="off" <?php  if(!$_SESSION['isAdmin']){
                                                                                               echo' value="'. $_SESSION['lecturer_name'] .'" 
                                                                                                disabled';
                                                                                            } ?>>
    <datalist id="lecturerList">
        <?php 
            while($row = $lecturers->fetch_assoc()) { 
                echo "<option data-value='{$row['lecturer_id']}' value='{$row['lecturer_name']}'>";
            }
        ?>
    </datalist><br>

    <?php if ($activityTypeId == 1 || $activityTypeId == 2 || $activityTypeId == 3 || $activityTypeId == 7 || $activityTypeId == 8 || $activityTypeId == 9 || $activityTypeId == 10)  : ?>
        <span style="color:red;font-size:x-large;"> * </span><label for="participation_type"> نوع المشاركة:</label>
        <input type="hidden" name="participation_type_id" id="participation_type_id">
        <input list="participationTypes" name="participation_type" id="participation_type"  autocomplete="off">
        <datalist id="participationTypes">
        <?php 
            while($row = $participations->fetch_assoc()) { 
                echo "<option data-value='{$row['participation_type_id']}' value='{$row['participation_type']}'>";
            }
        ?>
        </datalist><br>
    <?php endif; ?>

    <?php if ($activityTypeId == 2|| $activityTypeId == 3 || $activityTypeId == 8 || $activityTypeId == 9 || $activityTypeId == 10) { ?>
        <span style="color:red;font-size:x-large;"> * </span> <label for="date">من :</label>
        <input type="date" name="date" id="date"><br>

        <span style="color:red;font-size:x-large;"> * </span> <label for="date2">الى :</label>
        <input type="date" name="date2" id="date2"><br>

        <span style="color:red;font-size:x-large;"> * </span><label for="activity_name"> اسم النشاط :</label>
        <input type="text" list="activityNames" name="activity_name" id="activity_name"  autocomplete="off">
        <datalist id="activityNames">
                <?php while ($row = $activityNames->fetch_row()) { ?>
                    <option value="<?= $row[0] ?>"> <!-- 0 is activity_name  -->
                <?php } ?>
        </datalist><br>
        
        <span style="color:red;font-size:x-large;"> * </span><label for="file">ارفاق ملف:</label>
        <div id="imgfile"></div>
        <input type="file" name="file" id="file" ><br> 

        <label for="image">ارفاق صورة:(اختياري)</label>
        <div id="imagefile"></div>
        <input type="file" name="image" id="image"><br>


    <?php }elseif ($activityTypeId == 1 || $activityTypeId == 7) { ?>
        <span style="color:red;font-size:x-large;"> * </span><label for="date">التاريخ :</label>
        <input type="date" name="date" id="date"><br>
        
        <span style="color:red;font-size:x-large;"> * </span><label for="date2">الى :</label>
        <input type="date" name="date2" id="date2"><br>

        <span style="color:red;font-size:x-large;"> * </span><label for="activity_name">اسم الرسالة / الاطروحة / البحث :</label>
        <input type="text" list="activityNames" name="activity_name" id="activity_name"  autocomplete="off">
        <datalist id="activityNames">
                <?php while ($row = $activityNames->fetch_row()) { ?>
                    <option value="<?= $row[0] ?>"> <!-- 0 is activity_name  -->
                <?php } ?>
        </datalist><br>
        
        <span style="color:red;font-size:x-large;"> * </span><label for="file">ارفاق ملف:</label>
        <div id="imgfile"></div>
        <input type="file" name="file" id="file" ><br> 

        <label for="image">ارفاق صورة:(اختياري)</label>
        <div id="imagefile"></div>
        <input type="file" name="image" id="image"><br>

    <?php }elseif ($activityTypeId == 6) { ?>
        <span style="color:red;font-size:x-large;"> * </span><label for="acadimic_title">اللقب العلمي : </label>
        <!-- <input type="text" name="acadimic_title" id="acadimic_title"><br> -->
        <input type="hidden" name="acadimic_title_id" id="acadimic_title_id">
        <input list="titlesList" name="acadimic_title" id="acadimic_title"  autocomplete="off">
        <datalist id="titlesList">
        <?php 
            while($row = $Titles->fetch_assoc()) { 
                echo "<option data-value='{$row['acadimic_title_id']}' value='{$row['acadimic_title']}'>";
            }
        ?>
        </datalist><br>

        <span style="color:red;font-size:x-large;"> * </span><label for="university_order_date">تاريخ اصدار الامر الجامعي :</label>
        <input type="date" name="university_order_date" id="university_order_date"><br>

        <span style="color:red;font-size:x-large;"> * </span><label for="acadimic_title_order_date">تاريخ الحصول على اللقب العلمي :</label>
        <input type="date" name="acadimic_title_order_date" id="acadimic_title_order_date"><br>
        <span style="color:red;font-size:x-large;"> * </span><label for="major"> التخصص العام :</label>
        <input type="text" name="major" id="major" autocomplete="off"><br>
        <span style="color:red;font-size:x-large;"> * </span><label for="specialty">التخصص الدقيق :</label>
        <input type="text" name="specialty" id="specialty" autocomplete="off"><br>
        <span style="color:red;font-size:x-large;"> * </span><label for="file">ارفاق الامر الاجامعي:</label>
        <div id="imgfile"></div>
        <input type="file" name="file" id="file"><br> 
        <span style="color:red;font-size:x-large;"> * </span><label for="file2">ارفاق الامرالاداري:</label>
        <div id="imgfile"></div>
        <input type="file" name="file2" id="file2"><br> 
        <!-- <label for="image">ارفاق صورة:</label>888
        <input type="file" name="image" id="image"><br> -->

    <?php }elseif ($activityTypeId == 5) { ?>
        <span style="color:red;font-size:x-large;"> * </span> <label for="country">اسم البلد :</label>
        <input type="hidden" name="country_id" id="country_id">
        <input list="countryList" name="country" id="country"  autocomplete="off">
        <datalist id="countryList">
            <?php 
                while($row = $countries->fetch_assoc()) { 
                    echo "<option data-value='{$row['country_id']}' value='{$row['country']}'>";
                }
            ?>
        </datalist><br>
        <span style="color:red;font-size:x-large;"> * </span><label for="date">من :</label>
        <input type="date" name="date" id="date"><br>

        <span style="color:red;font-size:x-large;"> * </span><label for="date2">الى :</label>
        <input type="date" name="date2" id="date2"><br>

        <span style="color:red;font-size:x-large;"> * </span><label for="activity_name"> اسم النشاط :</label>
        <input type="text" list="activityNames" name="activity_name" id="activity_name"  autocomplete="off">
        <datalist id="activityNames">
                <?php while ($row = $activityNames->fetch_row()) { ?>
                    <option value="<?= $row[0] ?>"> <!-- 0 is activity_name  -->
                <?php } ?>
        </datalist><br>
        <span style="color:red;font-size:x-large;"> * </span><label for="file">ارفاق ملف:</label>
        <div id="imgfile"></div>
        <input type="file" name="file" id="file" ><br> 

        <label for="image">ارفاق صورة:(اختياري)</label>
        <div id="imagefile"></div>
        <input type="file" name="image" id="image"><br>
    <?php }elseif ($activityTypeId == 4) { ?>
        <span style="color:red;font-size:x-large;"> * </span><label for="activity_name"> اسم اللجنة :</label>
        <input type="text" name="activity_name" id="activity_name" autocomplete="off"><br>

        <span style="color:red;font-size:x-large;"> * </span><label for="date">التاريخ :</label>
        <input type="date" name="date" id="date"><br>
        
        <span style="color:red;font-size:x-large;"> * </span><label for="file">ارفاق ملف:</label>
        <div id="imgfile"></div>
        <input type="file" name="file" id="file" ><br> 

        <label for="image">ارفاق صورة:(اختياري)</label>
        <div id="imagefile"></div>
        <input type="file" name="image" id="image"><br>
        
    <?php } ?>

    <?php if($activityTypeId != 6){?>
        <span style="color:red;font-size:x-large;"> * </span><label for="activity_place"> مكان النشاط :</label>
        <input type="text" name="activity_place" id="activity_place" autocomplete="off"><br>
    <?php } ?>

    <label for="note">الملاحظات : </label><br>
    <textarea name="note" id="note" style="resize: both; width:100%" autocomplete="off"></textarea>
    

    <?php
    return ob_get_clean();
}

function getParticipationTypes($activityTypeId, $conn) {
    $query = "SELECT participation.participation_type_id, participation_type FROM participation JOIN activity_participation ON participation.participation_type_id = activity_participation.participation_type_id WHERE activity_participation.activity_type_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $activityTypeId);
    $stmt->execute();
    return $stmt->get_result();
}

// Check if the activity_type_id is set in the POST data
if (isset($_POST['activity_type_id'])) {
    $selectedActivityTypeId = $_POST['activity_type_id'];

    $cond="";
    if(!$_SESSION['isAdmin'])
        $cond=" AND lecturer_id=".$_SESSION["lecturer_id"];
    // Execute queries to fetch data for dropdowns
    $lecturers = executeQuery($conn, 'SELECT * FROM lecturers WHERE deleted=0 '.$cond);
    $participations = getParticipationTypes($selectedActivityTypeId, $conn);
    $activityNames = executeQuery($conn, 'SELECT DISTINCT activity_name FROM activity_information');
    $countries = executeQuery($conn, 'SELECT * FROM countries');
    $Titles = executeQuery($conn, 'SELECT * FROM acadimic_titles');

    // Call dynamicFields function with the selected activity_type_id
    $dynamicFields = dynamicFields($selectedActivityTypeId, $lecturers, $participations, $activityNames, $countries,$Titles);

    // Return the generated dynamic fields as the AJAX response
    echo $dynamicFields;
}
?>
