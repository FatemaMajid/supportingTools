<?php

    require_once("checkLogin.php");
    require_once("configuration.php");

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

   
    if($_SESSION['role'] == 'admin'){
            header("Location:home.php");
            exit;
    }

 
    // Check if search parameters are provided in the URL query string
    $search_lec_Name = isset($_GET['search_lec_Name']) ? '%' . $_GET['search_lec_Name'] . '%' : '';
    $search_act_type = isset($_GET['search_act_type']) ? '%' . $_GET['search_act_type'] . '%' : '';
    $search_dt1 = isset($_GET['search_dt1']) ?   $_GET['search_dt1']  : '';
    $search_dt2 = isset($_GET['search_dt2']) ?  $_GET['search_dt2']  : '';


    $sqlQuery = "SELECT activity_id, lecturer_name, activity_type, activity_date, activity_to_date, activity_name, activity_place, participation_type, activity_type_id ,file , image FROM (
                SELECT ai.activity_id, l.lecturer_name, a_t.activity_type, ai.activity_date, ai.activity_to_date, ai.activity_name, ai.activity_place, pt.participation_type, ai.activity_type_id, f.file, i.image
                FROM activity_information ai
                LEFT JOIN lecturers l ON ai.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON ai.activity_type_id = a_t.activity_type_id
                LEFT JOIN participation pt ON ai.participation_type_id = pt.participation_type_id
                LEFT JOIN files f ON ai.file_id = f.file_id
                LEFT JOIN images i ON ai.image_id = i.image_id
                WHERE l.email_key = ? 
                UNION
                SELECT com.activity_id, l.lecturer_name, a_t.activity_type, com.activity_date, NULL AS activity_to_date, com.activity_name, com.activity_place, NULL AS participation_type, com.activity_type_id , f.file , i.image 
                FROM committees com
                LEFT JOIN lecturers l ON com.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON com.activity_type_id = a_t.activity_type_id
                LEFT JOIN files f ON com.file_id = f.file_id
                LEFT JOIN images i ON com.image_id = i.image_id
                WHERE l.email_key = ?
                UNION
                SELECT mis.mission_id AS activity_id , l.lecturer_name, a_t.activity_type,  mis.from_date AS activity_date , mis.to_date AS activity_date, mis.activity_name, mis.activity_place, NULL AS participation_type, a_t.activity_type_id , f.file , i.image
                FROM missions mis
                LEFT JOIN lecturers l ON mis.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON mis.activity_type_id = a_t.activity_type_id
                LEFT JOIN files f ON mis.file_id = f.file_id
                LEFT JOIN images i ON mis.image_id = i.image_id
                WHERE l.email_key = ?
                UNION
                SELECT pro.promotion_id AS activity_id , l.lecturer_name, a_t.activity_type,  pro.university_order_date  AS activity_date , pro.acadimic_title_order_date AS activity_to_date, NULL AS activity_name, NULL AS activity_place, NULL AS participation_type, a_t.activity_type_id , f.file ,  f.file
                FROM promotions pro
                LEFT JOIN lecturers l ON pro.lecturer_id = l.lecturer_id
                LEFT JOIN activity_type a_t ON pro.activity_type_id = a_t.activity_type_id
                LEFT JOIN files f ON pro.file_id = f.file_id AND pro.file_id2 = f.file_id
                LEFT JOIN acadimic_titles act ON pro.acadimic_title_id = act.acadimic_title_id
                 WHERE l.email_key = ?
            ) AS combined_activities ";

        $params = [$_SESSION['google_email'], $_SESSION['google_email'], $_SESSION['google_email'], $_SESSION['google_email']];
        $types = 'ssss';


    // $params = [];
    // $types = '';

    if (!empty($search_lec_Name) || !empty($search_act_type) || (!empty($_GET['search_dt1']) && !empty($_GET['search_dt2']))) {
        $sqlQuery .= " WHERE ";
        $conditions = array();
        if (!empty($search_lec_Name)) {
            $conditions[] = "lecturer_name LIKE ?";
            $params[] = $search_lec_Name;
            $types .= 's';
        }
        if (!empty($search_act_type)) {
            $conditions[] = "activity_type LIKE ?";
            $params[] = $search_act_type;
            $types .= 's';
        }
        if (!empty($_GET['search_dt1']) && !empty($_GET['search_dt2'])) {
            $conditions[] = "activity_date BETWEEN ? AND ?";
            $params[] = $_GET['search_dt1'];
            $params[] = $_GET['search_dt2'];
            $types .= 'ss';
        } elseif (!empty($_GET['search_dt1'])) {
            $conditions[] = "activity_date = ?";
            $params[] = $_GET['search_dt1'];
            $types .= 's';
        }
        $sqlQuery .= implode(" AND ", $conditions);
    }


    if (!empty($params)) {
        $sql = $conn->prepare($sqlQuery);
        if ($sql) {
            $sql->bind_param($types, ...$params);
            $sql->execute();
            $result = $sql->get_result();
        } else {
            // Handle prepare error
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        }
    } else {
        $result = $conn->query($sqlQuery);
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" dir="rtl">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #e6f3ff;
        }

        
        .search_form-row {
            margin-bottom: 20px;
        }

        .search_form-group {
            margin-bottom: 0;
        }

        .btn-primary {
            background-color: #1d7957;
            border-color: #1d7957;
        }

        .btn-primary:hover {
            background-color: #68a18c;
            border-color: #68a18c;
        }

        .modal-dialog {
            max-width: 800px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #2e7d32;
            padding: 8px;
            width: 400px; 
        }

        th {
            background-color: #00796b;
            color: white;
        }

        a {
            text-decoration: none;
            color: #00796b; 
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
            color: #004d40; 
        }

    </style>

<style>
        #modal-details {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f2f2f2;
            direction: rtl;
        }

        #modal-details form {
            margin: 0 auto;
            width: 50%;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #modal-details label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            
        }

        #modal-details input[type="text"],
        #modal-details input[type="date"] {
            width: calc(100% - 12px);
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #modal-details input[type="text"]:disabled {
            background-color: #f2f2f2;
            cursor: not-allowed;
        }

        #modal-details  #editButton {
            /* background-color: #007bff; */
            color: #004d40;
            /* padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px; */
        }

        /* #modal-details #editButton:hover {
            background-color: #0056b3;
        } */
    </style>

</head>

<body>
<?php include "header.php" ?>

    <div class="container mt-5" dir="rtl">
    <form method="GET" class="search_form">
    <h2>البحث عن طريق :</h2>
    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="search_lec_Name">الاسم : </label>
            <input type="text" class="form-control" id="search_lec_Name" name="search_lec_Name"
                value="<?php echo htmlspecialchars(trim($_SESSION['lecturer_name'], '%')); ?>" readonly>
        </div>
        <div class="form-group col-md-4">
            <label for="search_act_type">نوع النشاط : </label>
            <input type="text" class="form-control" id="search_act_type" name="search_act_type"
                value="<?php echo htmlspecialchars(trim($search_act_type, '%')); ?>" autocomplete="off">
        </div>
        <div class="form-group col-md-2">
            <label for="search_dt1">من تاريخ:</label>
            <input type="date" class="form-control" id="search_dt1" name="search_dt1"
                value="<?php echo htmlspecialchars($search_dt1); ?>">
            <input type="hidden" id="search_dt1_hidden" name="search_dt1_hidden"
                value="<?php echo htmlspecialchars($search_dt1); ?>">
            <script>
                document.getElementById('search_dt1').addEventListener('change', function () {
                    document.getElementById('search_dt1_hidden').value = this.value;
                    this.setAttribute('value', this.value); // Update the visible input field
                });
            </script>
        </div>
        <div class="form-group col-md-2">
            <label for="search_dt2">الى تاريخ:</label>
            <input type="date" class="form-control" id="search_dt2" name="search_dt2"
                value="<?php echo htmlspecialchars($search_dt2); ?>">
            <input type="hidden" id="search_dt2_hidden" name="search_dt2_hidden"
                value="<?php echo htmlspecialchars($search_dt2); ?>">
            <script>
                document.getElementById('search_dt2').addEventListener('change', function () {
                    document.getElementById('search_dt2_hidden').value = this.value;
                    this.setAttribute('value', this.value); // Update the visible input field
                });
            </script>
        </div>
        <div class="form-group col-md-2">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary form-control">بحث</button> <br>
        </div>
    </div>
</form>

        
        
        <div class="row">
        <div class="col-md-6">
        <a href="#" class="btn btn-primary export-all" >تصدير جميع المعلومات</a>
            <a href="#" class="btn btn-primary export-search">تصدير حسب البحث</a>
        </div>

        </div>
        <h2>نشاطات التدريسيين</h2>
        <button type="button"><a href="user_insert.php">اضافة</a></button>
        <table class="table">
            <thead>
                <tr>
                    <th>اسم التدريسي</th>
                    <th>نوع النشاط</th>
                    <th>تاريخ النشاط</th>
                    <th style="width: 900px;">اسم النشاط</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['lecturer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['activity_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['activity_date']); ?></td>
                    <td style="width: 900px;"><?php echo htmlspecialchars($row['activity_name']); ?></td> 
                    <td>
                        <button class="btn btn-info view-details" data-toggle="modal" data-target="#detailsModal"
                            data-activityid='<?php echo $row['activity_id']; ?>'
                            data-activitytypeid='<?php echo $row['activity_type_id']; ?>'>
                            عرض التفاصيل
                        </button>
                    </td>
                    <td>
                    <button class="btn btn-danger delete-record" data-activityid='<?php echo $row['activity_id']; ?>' data-activitytypeid='<?php echo $row['activity_type_id']; ?>'>
                            حذف
                        </button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Details</h5> 
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div> -->
                <div class="modal-body">
                    <div id="modal-details" >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "footer.php" ?>

    <script>
        $(document).ready(function () {
            $('.view-details').click(function () {
                var activityid = $(this).data('activityid');
                var activitytypeid = $(this).data('activitytypeid');
                $.ajax({
                    type: 'GET',
                    url: 'edit.php',
                    data: { activity_id: activityid, activity_type_id: activitytypeid }, 
                    success: function (response) {
                        $('#modal-details').html(response);
                        
                    }
                });
            });

            $('.export-all').click(function () {
            var activitytypeid = $(this).data('activitytypeid');
            // Redirect to the export PHP file with the activity type ID as a parameter
            window.location = 'export.php';
        });

            $('.export-search').click(function () {
                var search_lec_Name = $('#search_lec_Name').val();
                var search_act_type = $('#search_act_type').val();
                var search_dt1 = $('#search_dt1').val();
                var search_dt2 = $('#search_dt2').val();

                var url = 'exportSearch.php';
                url += '?search_lec_Name=' + search_lec_Name + '&search_act_type=' + search_act_type + '&search_dt1=' + search_dt1 + '&search_dt2=' + search_dt2;

                window.location = url;
            });

            


            $('.delete-record').click(function () {
                var activityid = $(this).data('activityid');
                var activitytypeid = $(this).data('activitytypeid');

                // Show a confirmation dialog before proceeding with the deletion
                if (confirm("هل انت متأكد من حذف هذا النشاط ؟")) {
                    // If the user clicks "OK" in the confirmation dialog, proceed with the deletion
                    $.ajax({
                        type: 'GET',
                        url: 'delete.php',
                        data: { activity_id: activityid, activity_type_id: activitytypeid },
                        success: function (response) {
                            alert(response);
                            location.reload();
                        }
                    });
                } else {
                    // If the user clicks "Cancel" in the confirmation dialog, do nothing
                    return false;
                }
            });

        });

    </script>

</body>

</html>
