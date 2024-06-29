<?php
     $forAdmin=true;
     require_once("checkLogin.php");
     require_once("configuration.php");
     

    $sql = "SELECT * FROM lecturers ORDER BY role,lecturer_name";
    $result = $conn->query($sql);

    $users = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    if(isset($_POST['change_type'])){
        $lecturer_id = $_POST['lecturer_id'];
        $new_type = $_POST['new_type'];

        if ($new_type == 'admin') {
            $stmt = $conn->prepare("UPDATE lecturers SET role = 'admin' WHERE lecturer_id = ?");
            $stmt->bind_param('i', $lecturer_id);
            $stmt->execute();
        }else if ($new_type == 'Lecturer') {
            $stmt = $conn->prepare("UPDATE lecturers SET role = 'Lecturer' WHERE lecturer_id = ?");
            $stmt->bind_param('i', $lecturer_id);
            $stmt->execute();
        }
        header('Location: admin.php');
        exit;
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include "header.php" ?>
    <div class="container" style="text-align: center;">
        <h1 >المستخدمين </h1>
        <table class="table">
            <thead>
                <tr>
                     <th>اسم المستخدم</th>
                    <th>البريد الالكتروني</th>
                    <th>الصلاحية</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user){ ?>
                    <tr>
                        <td><?= $user['lecturer_name'] ?></td>
                        <td><?= $user['email_key'] ?></td>
                        <td><?= $user['role'] ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="lecturer_id" value="<?= $user['lecturer_id'] ?>">
                                <select name="new_type">
                                    <option value="admin" <?php if($user['role'] == 'admin') echo 'selected'; ?>>مدير</option>
                                    <option value="Lecturer" <?php if($user['role'] == 'Lecturer') echo 'selected'; ?>>تدريسي</option>
                                </select>
                                <button type="submit" name="change_type" class="btn btn-primary">تغيير الصلاحية</button>
                            </form>
                            
                        </td>
                      
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
    </div>
    <section>
     <?php include "footer.php" ?>
    </section>
</body>
</html>
