<?php
Global $conn, $client, $FileDir, $imagePath;
$conn = mysqli_connect('localhost', 'root', '', 'activities') or die('connection failed');
$conn->set_charset("utf8");
$FileDir = "C:/project_files/";
$_SESSION['file_id'] = 0;
require('./vendor/autoload.php');

// تحميل متغيرات البيئة
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// client ID and Secret
$client_id = getenv('GOOGLE_CLIENT_ID');
$client_secret = getenv('GOOGLE_CLIENT_SECRET');

// $client = new Google_Client();
// $client->setClientId($client_id);
// $client->setClientSecret($client_secret);

// redirection location is the path to login.php
$redirect_uri = 'http://localhost/graduation_project/login.php';
// $client->setRedirectUri($redirect_uri);
// $client->addScope("email");
// $client->addScope("profile");
$google_oauth_version = 'v3';
$params = [
    'response_type' => 'code',
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
    'access_type' => 'offline',
    'prompt' => 'consent'
];
$login_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query($params);
?>
