<?php
      require_once("checkLogin.php");
      require_once("configuration.php");
     //session_start();
     
    //  if (!isset($_SESSION['google_loggedin'])) {
    //     header('Location: login.php');
    //     exit;
    // }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="shortcut icon" href="image/coffee-world-website-favicon-22color.png" type="image/x-icon">
    <title>Document</title>
</head>
<body>


<nav class="navbar navbar-expand-lg navbar-dark bg-dark" dir="ltr">
    <!-- <a class="navbar-brand" href="#">Navbar</a> -->
    <div class="user-box" style="position: relative; float: right; padding: 10px;">
    <i class="bx bxs-user" id="user-btn" style="cursor: pointer; color:#f9f9f9;"></i>
    <div id="user-dropdown" style="padding: 15px;display: none; position: absolute; background-color: #f9f9f9; min-width: 160px; box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2); z-index: 1;">
        <p><?php echo $_SESSION['lecturer_name'] ;?></p>
        <p><?php echo $_SESSION['google_email'] ;?></p>
        <button><a href="logout.php" >logout</a></button>
    </div>
</div>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
           
            <?php
                if($_SESSION['isAdmin']){
                    echo '<li class="nav-item">
                            <a class="nav-link" href="admin.php">اعدادات المستخدمين</a>
                        </li>';
                }
            ?>
            <li class="nav-item">
                <a class="nav-link" href="
                <?php 
                        if($_SESSION['isAdmin'])
                            echo "insert_t.php";
                        else    
                            echo "user_insert.php";
                        ?>">اضافة</a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="
                    <?php 
                        if($_SESSION['isAdmin'])
                            echo "home.php";
                        else    
                            echo "user_home.php";
                        ?>
                        ">الصفحة الرئيسية <span class="sr-only">(current)</span></a>
            </li>
            
        </ul>
    </div>
</nav>

<script>
    document.getElementById("user-btn").addEventListener("click", function() {
        var dropdown = document.getElementById("user-dropdown");
        if (dropdown.style.display === "none" || dropdown.style.display === "") {
            dropdown.style.display = "block";
        } else {
            dropdown.style.display = "none";
        }
    });
</script>

</body>
</html>
