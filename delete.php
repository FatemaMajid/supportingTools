<?php

require_once("checkLogin.php");
require_once("configuration.php");

if(isset($_GET['activity_id']) && isset($_GET['activity_type_id'])){
    $activity_id = $_GET['activity_id']; 
    $activity_type_id = $_GET['activity_type_id'];

    $cond="";
    if(!$_SESSION['isAdmin'])
        $cond=" AND lecturer_id=".$_SESSION["lecturer_id"];

    if ($activity_type_id == 1 || $activity_type_id == 2 || $activity_type_id == 3 || $activity_type_id == 7 || $activity_type_id == 8 || $activity_type_id == 9 || $activity_type_id == 10) {
        $stmt = $conn->prepare("DELETE FROM activity_information WHERE activity_id = ?".$cond);
    } elseif ($activity_type_id == 4) {
        $stmt = $conn->prepare("DELETE FROM committees WHERE activity_id = ?".$cond);
    } elseif ($activity_type_id == 5) {
        $stmt = $conn->prepare("DELETE FROM missions WHERE mission_id = ?".$cond);
    }elseif ($activity_type_id == 6) {
        $stmt = $conn->prepare("DELETE FROM promotions WHERE promotion_id = ?".$cond);
    }

    $stmt->bind_param('i', $activity_id);
    
    if($stmt->execute()) {
        echo "تم الحذف بنجاح";
    } else {
        echo "حدث خطأ خلال عملية الحذف: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
