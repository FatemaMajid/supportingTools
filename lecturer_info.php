<?php
    require_once("checkLogin.php");
    require_once("configuration.php");

    $query = "";

    if(isset($_GET['lecturer_id'])) {
        $lecturer_id = $_GET['lecturer_id'];
        $activity_type_id = $_GET['activity_type_id'];
        if($activity_type_id==6){
            $query = "SELECT l.major, l.specialty
                        FROM lecturers l
                        WHERE l.lecturer_id = ?";
                        
            $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $lecturer_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $LecturerInfo = [];
                while($row = $result->fetch_assoc()) {
                    $LecturerInfo[] = $row;
                }

                echo json_encode($LecturerInfo);        
            }
            else 
            echo json_encode([""]);
    }

    
?>
