<?php
     require_once("checkLogin.php");
     require_once("configuration.php");
     
    $query = "";

    if(isset($_GET['date'])) {
        $date = $_GET['date'];
        $activity_type_id = $_GET['activity_type_id'];

        if ($activity_type_id == 1 || $activity_type_id == 2 || $activity_type_id == 3 || $activity_type_id == 7 || $activity_type_id == 9 || $activity_type_id == 10 || $activity_type_id == 8) {
            $query = "SELECT ai.activity_id, l.lecturer_name, a_t.activity_type, ai.activity_date, ai.activity_to_date, ai.activity_name, ai.activity_place, pt.participation_type ,f.file,f.file_id,i.image,i.image_id
                                    FROM activity_information ai
                                    LEFT JOIN lecturers l ON ai.lecturer_id = l.lecturer_id
                                    LEFT JOIN activity_type a_t ON ai.activity_type_id = a_t.activity_type_id
                                    LEFT JOIN participation pt ON ai.participation_type_id = pt.participation_type_id
                                    LEFT JOIN files f ON ai.file_id = f.file_id
                                    LEFT JOIN images i ON ai.image_id = i.image_id
                                    WHERE ai.activity_date = ? AND ai.activity_type_id = ?";
        } elseif ($activity_type_id == 4) {
            $query = "SELECT com.activity_id, l.lecturer_name, a_t.activity_type, com.activity_date, NULL AS activity_to_date, com.activity_name, com.activity_place, NULL AS participation_type, f.file,f.file_id,i.image,i.image_id
                                    FROM committees com
                                    LEFT JOIN lecturers l ON com.lecturer_id = l.lecturer_id
                                    LEFT JOIN activity_type a_t ON com.activity_type_id = a_t.activity_type_id
                                    LEFT JOIN files f ON com.file_id = f.file_id
                                    LEFT JOIN images i ON com.image_id = i.image_id
                                    WHERE com.activity_date = ? AND com.activity_type_id = ?";
        } elseif ($activity_type_id == 5) {
            $query = "SELECT mis.mission_id AS activity_id, l.lecturer_name, a_t.activity_type, coun.country , mis.from_date AS activity_date, mis.to_date AS activity_to_date, mis.activity_name, mis.activity_place , f.file,f.file_id , i.image , i.image_id
                        FROM missions mis
                        LEFT JOIN lecturers l ON mis.lecturer_id = l.lecturer_id
                        LEFT JOIN activity_type a_t ON mis.activity_type_id = a_t.activity_type_id
                        LEFT JOIN countries coun ON mis.country_id = coun.country_id
                        LEFT JOIN files f ON mis.file_id = f.file_id
                        LEFT JOIN images i ON mis.image_id = i.image_id
                        WHERE mis.from_date = ? AND mis.activity_type_id = ?";
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $date, $activity_type_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $activities = [];
        while($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }

        echo json_encode($activities);
    } else if (isset($_GET['activity_name'])) {
        $activityName = $_GET['activity_name'];
        $activity_type_id = $_GET['activity_type_id'];

        if ($activity_type_id == 1 || $activity_type_id == 2 || $activity_type_id == 3 || $activity_type_id == 7 || $activity_type_id == 9|| $activity_type_id == 10 || $activity_type_id == 8) {
            $query = "SELECT ai.activity_id, l.lecturer_name, a_t.activity_type, ai.activity_date, ai.activity_to_date, ai.activity_name, ai.activity_place, pt.participation_type ,f.file,f.file_id,i.image,i.image_id
                                FROM activity_information ai
                                LEFT JOIN lecturers l ON ai.lecturer_id = l.lecturer_id
                                LEFT JOIN activity_type a_t ON ai.activity_type_id = a_t.activity_type_id
                                LEFT JOIN participation pt ON ai.participation_type_id = pt.participation_type_id
                                LEFT JOIN files f ON ai.file_id = f.file_id
                                LEFT JOIN images i ON ai.image_id = i.image_id
                                WHERE ai.activity_name LIKE ? AND ai.activity_type_id = ?";
        } elseif ($activity_type_id == 4) {
            $query = "SELECT com.activity_id, l.lecturer_name, a_t.activity_type, com.activity_date, NULL AS activity_to_date, com.activity_name, com.activity_place, NULL AS participation_type, f.file,f.file_id,i.image,i.image_id
                                FROM committees com
                                LEFT JOIN lecturers l ON com.lecturer_id = l.lecturer_id
                                LEFT JOIN activity_type a_t ON com.activity_type_id = a_t.activity_type_id
                                LEFT JOIN files f ON com.file_id = f.file_id
                                LEFT JOIN images i ON com.image_id = i.image_id
                                WHERE com.activity_name LIKE ? AND com.activity_type_id = ?";
        } elseif ($activity_type_id == 5) {
            $query = "SELECT mis.mission_id AS activity_id, l.lecturer_name, a_t.activity_type, coun.country , mis.from_date AS activity_date, mis.to_date AS activity_to_date, mis.activity_name, mis.activity_place , f.file,f.file_id , i.image , i.image_id
                        FROM missions mis
                        LEFT JOIN lecturers l ON mis.lecturer_id = l.lecturer_id
                        LEFT JOIN activity_type a_t ON mis.activity_type_id = a_t.activity_type_id
                        LEFT JOIN countries coun ON mis.country_id = coun.country_id
                        LEFT JOIN files f ON mis.file_id = f.file_id
                        LEFT JOIN images i ON mis.image_id = i.image_id
                        WHERE mis.activity_name LIKE ? AND mis.activity_type_id = ?";
        }

        $stmt = $conn->prepare($query);
        $activityName = "%" . $activityName . "%";
        $stmt->bind_param("si", $activityName, $activity_type_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $activities = [];
        while($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }

        echo json_encode($activities);
    } else {
        echo "no parameter provided.";
    }

    $conn->close();
?>