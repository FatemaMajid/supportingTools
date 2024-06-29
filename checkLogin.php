<?php
    if(session_status() !== PHP_SESSION_ACTIVE) session_start();

    if (!isset($_SESSION['google_loggedin'])) {
        header('Location: login.php');
        exit;
    }

    if(isset($forAdmin) && $forAdmin){
        if($_SESSION['role'] == 'admin'){
            
            
        }else{
            header("Location:invalid.php");
        }
    }
?>