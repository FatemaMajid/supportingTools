<?php
require_once("checkLogin.php");
require_once("configuration.php");

function getSubActivities($activityTypeId, $conn) {
    $query = "SELECT sub_activity_id, sub_activity_type FROM sub_activities  WHERE sub_activities.activity_type_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $activityTypeId);
    $stmt->execute();
    $activityTypes= $stmt->get_result();
    while ($row = $activityTypes->fetch_assoc()) {
        echo "<option data-value='{$row['sub_activity_id']}' value='{$row['sub_activity_type']}'>";
    }
    
}

if (isset($_POST['activity_type_id'])) {
    $activityTypeId = (int)$_POST['activity_type_id'];
    getSubActivities($activityTypeId, $conn);

}

?>