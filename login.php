<?php
    session_start();
    require_once("configuration.php");


    # expire in 1 hour
    // session_set_cookie_params(3600);

    if (isset($_GET['code']) && !empty($_GET['code'])) {
        // Execute cURL request to retrieve the access token
        $params = [
            'code' => $_GET['code'],
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        // Make sure access token is valid
        if (isset($response['access_token']) && !empty($response['access_token'])) {
            // Execute cURL request to retrieve the user info associated with the Google account
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/' . $google_oauth_version . '/userinfo');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
            $response = curl_exec($ch);
            curl_close($ch);
            $profile = json_decode($response, true);
            // Make sure the profile data exists
            if (isset($profile['email'])) {
                $google_name_parts = [];
                $google_name_parts[] = isset($profile['given_name']) ? preg_replace('/[^a-zA-Z0-9]/s', '', $profile['given_name']) : '';
                $google_name_parts[] = isset($profile['family_name']) ? preg_replace('/[^a-zA-Z0-9]/s', '', $profile['family_name']) : '';
                // Authenticate the user
                session_regenerate_id();

                ////////////////////////////////////////////////////////////
                    // $conn->begin_transaction();
                    // $conn->query("LOCK TABLE lecturers WRITE");
                    // header("Location:temp.php");
                    // //  exit;
                    // $conn->query("UNLOCK TABLES"); 
                    // $conn->commit();
                    // $conn->rollback(); 
                //////////////////////////////////////////////////////////


                //check if email already exists in database
                $select_user=$conn->prepare("SELECT * FROM lecturers WHERE email_key=? ");
                $select_user->bind_param("s", $profile['email']);
                $select_user->execute();
                $result = $select_user->get_result();
                if($result->num_rows > 0){
                    $row = $result->fetch_assoc();
                    $_SESSION['role'] =$row['role'];
                    $_SESSION['lecturer_id'] =$row['lecturer_id'];
                    if($row['role'] == 'admin'){
                        $_SESSION['google_email'] = $row['email_key'];
                        $_SESSION['google_loggedin'] = TRUE;
                        $_SESSION['isAdmin'] = TRUE;
                        // $_SESSION['google_email'] = $profile['email'];
                        // $_SESSION['google_name'] = implode(' ', $google_name_parts);
                        $_SESSION['lecturer_name'] = $row['lecturer_name'];
                        header('location:home.php');
                        exit;
                    } else {
                        $_SESSION['google_email'] = $row['email_key'];
                        $_SESSION['google_loggedin'] = TRUE;
                        $_SESSION['isAdmin'] = false;
                        // $_SESSION['google_email'] = $profile['email'];
                        // $_SESSION['google_name'] = implode(' ', $google_name_parts);
                        $_SESSION['lecturer_name'] = $row['lecturer_name'];
                        header('location:user_home.php');
                        exit;
                    }
                
                } else {
                    echo "<script>
                            alert('يتعذر تسجيل الدخول,الحساب غير صحيح');";
                    echo"window.location.href = '".$_SERVER["PHP_SELF"]."';
                        </script>";
                    exit;
                }
                
                
            } else {
                exit('Could not retrieve profile information! Please try again later!');
            }
        } else {
            exit('Invalid access token! Please try again later!');
        }
    } 
    // else {
    //     // Define params and redirect to Google Authentication page
    //     $params = [
    //         'response_type' => 'code',
    //         'client_id' => $client_id,
    //         'redirect_uri' => $redirect_uri,
    //         'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
    //         'access_type' => 'offline',
    //         'prompt' => 'consent'
    //     ];
    //     header('Location: https://accounts.google.com/o/oauth2/auth?' . http_build_query($params));
    //     exit;
    // }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e6f3ff;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .login-form img {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>تسجيل الدخول</h2>
            <hr>
            <img src="images/logo2.png" alt="Google Logo"> <h4>يرجى تسجيل الدخول باستخدام الحساب الجامعي</h4>
            <hr>
            <a href="<?= $login_url ?>" class="btn btn-primary">تسجيل</a>
        </div>
    </div>
</body>
</html>
