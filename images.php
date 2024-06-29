<?php
 require_once("checkLogin.php");
 require_once("configuration.php");

 global $FileDir ;
if (isset($_GET['image_id'])) {
    $image_id = $_GET['image_id'];

    $sql = $conn->prepare("SELECT image FROM images WHERE image_id = ?");
    $sql->bind_param("i", $image_id);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $FileDir.$row['image'];

        if (file_exists($image_path)) {
            // Determine the content type based on the file extension
            $image_extension = pathinfo($image_path, PATHINFO_EXTENSION);
            switch ($image_extension) {
                case 'jpg':
                    $content_type = 'image/jpeg';
                    break;
                case 'png':
                    $content_type = 'image/png';
                    break;
                case 'bmp':
                    $content_type = 'image/bmp';
                    break;
                case 'pdf':
                    $content_type = 'application/pdf';
                    break;
                default:
                    $content_type = 'application/octet-stream';
            }

            // Set headers to force download or display in browser
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: inline; filename="' . basename($image_path) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($image_path));
            ob_clean();
            flush();
            readfile($image_path);
            exit;
        } else {
            echo 'image not found.';
        }
    } else {
        echo 'image not found.';
    }
} else {
    echo 'Invalid file ID.';
}
?>
