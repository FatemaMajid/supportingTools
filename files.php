<?php
 require_once("checkLogin.php");
 require_once("configuration.php");

 global $FileDir ;
if (isset($_GET['file_id'])) {
    $file_id = $_GET['file_id'];

    $sql = $conn->prepare("SELECT file FROM files WHERE file_id = ?");
    $sql->bind_param("i", $file_id);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = $FileDir.$row['file'];

        if (file_exists($file_path)) {
            // Set headers to force download or display in browser
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            ob_clean();
            flush();
            readfile($file_path);
            exit;
        } else {
            echo 'File not found.';
        }
    } else {
        echo 'File not found.';
    }
} else {
    echo 'Invalid file ID.';
}
?>
