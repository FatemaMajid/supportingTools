<?php
    include("configuration.php");

    $api_url = 'https://cv.nahrainuniv.edu.iq/api.php?type=staff&did=1&lang=ar#';

    // Read JSON file
    $json_data = file_get_contents($api_url);

    // Decode JSON data into PHP array
    $response_data = json_decode($json_data);

    // All user data exists in 'data' object
    $user_data = $response_data;

    // Print data if need to debug
    // print_r($user_data);

    $stmt = $conn->prepare("DELETE FROM temp ");
    $stmt->execute();

    // Traverse array and display user data
    $stmt = $conn->prepare("INSERT INTO temp (sid, depid, dep_text,	name_text,	email_key,	major,	specialty,	qualification_text,	stitle_text ,gender_text) VALUES (?, ?,?, ?,?, ?,?, ?,?,?)");
    foreach ($user_data as $user) {
        $stmt->bind_param("iissssssss",$user->sid, $user->depid, $user->dep_text, $user->name_text,	$user->email_key, $user->major,	$user->specialty, $user->qualification_text, $user->stitle_text ,$user->gender_text);
        $stmt->execute();
    }

// Update all lecturers from Temp
        // UPDATE `lecturer` JOIN temp ON lecturer.id = temp.sid 
        //   SET lecturer.`depid`=temp.`depid`,
        //   lecturer.`dep_text`=temp.`dep_text`
        $stmt = $conn->prepare("UPDATE lecturers JOIN temp ON lecturers.lecturer_id = temp.sid SET lecturers.depid = temp.depid, lecturers.dep_text = temp.dep_text, lecturers.lecturer_name = temp.name_text, lecturers.email_key = temp.email_key, lecturers.major = temp.major, lecturers.specialty = temp.specialty, lecturers.qualification_text = temp.qualification_text, lecturers.stitle_text = temp.stitle_text, lecturers.gender_text = temp.gender_text");
        $stmt->execute();
// Insert all lecturers in Temp and not exists in lecturer
        // INSERT INTO `lecturer`(`lecturer_id`, `depid`, `dep_text`, `lecturer_name`, `email_key`, `major`, `specialty`, `qualification_text`, `stitle_text`, `gender_text`) 
        // SELECT `sid`, `depid`, `dep_text`, `name_text`, `email_key`, `major`, `specialty`, `qualification_text`, `stitle_text`, `gender_text` FROM temp 
        // WHERE sid NOT IN (SELECT id FROM lecturer)
        $stmt = $conn->prepare("INSERT INTO lecturers (lecturer_id,depid, dep_text, lecturer_name, email_key, major, specialty, qualification_text, stitle_text, gender_text) 
        SELECT sid,depid, dep_text, name_text, email_key, major, specialty, qualification_text, stitle_text, gender_text FROM temp 
        WHERE sid NOT IN (SELECT lecturer_id FROM lecturers)");
        $stmt->execute();
//Set disable to all lecturers in lecturer table and not exists in temp
        // UPDATE `lecturer` SET `deleted`=1
        // WHERE id NOT IN (SELECT sid FROM temp)
        $stmt = $conn->prepare("UPDATE lecturers SET deleted = 1 WHERE lecturer_id NOT IN (SELECT sid FROM temp)");
        $stmt->execute();

    
    // $check_query = "SELECT * FROM lecturer WHERE id = ? ";
    // $check_stmt = $conn->prepare($check_query);
    // $check_stmt->bind_param("iis", $params[0], $params[1], $params[4]); 
    // $check_stmt->execute();
    // $check_result = $check_stmt->get_result();
?>