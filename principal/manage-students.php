<?php
session_start();
require_once '../config.php';
 $college_code = $_SESSION['college_code'];
$college_name = $_SESSION['college_name'];
// Fetch branch_code and semester from the GET parameters
$branch = $_GET['branch_code'];
$semester = $_GET['semester'];
$message = '';

function generateToken($length = 20) {
    // Generate a random binary string and convert it to hexadecimal
    $token = bin2hex(random_bytes($length / 2));
    return $token;
}



// Handle the form submission for upgrading the semester
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_semester'])) {
    $current_semester = $_POST['current_semester'];

    // Upgrade semester logic: Increment semester by 1
    $upgradeQuery = "UPDATE students SET semester = semester + 1 WHERE semester = ? AND college_code = '$college_code' AND branch = $branch";
    $stmt = $conn->prepare($upgradeQuery);
    $stmt->bind_param('i', $semester);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "Semester upgraded successfully for all students in $current_semester semester.";
    } else {
        $message = "No students found in $current_semester semester or upgrade failed.";
    }

    $stmt->close();
}

// Handle the form submission for degrading the semester
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['degrade_semester'])) {
    $current_semester = $_POST['current_semester'];

    // Upgrade semester logic: Increment semester by 1
    $upgradeQuery = "UPDATE students SET semester = semester - 1 WHERE semester = ? AND college_code = '$college_code' AND branch = $branch";
    $stmt = $conn->prepare($upgradeQuery);
    $stmt->bind_param('i', $semester);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "Semester degraded successfully for all students in $current_semester semester.";
    } else {
        $message = "No students found in $current_semester semester or upgrade failed.";
    }

    $stmt->close();
}

// Handle add faculty form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_faculty'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $without_password = $_POST['password'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $number = $_POST['number'];
    $reg = $_POST['reg'];
   
  // Usage
    $token = generateToken();
  

    $stmt = $conn->prepare("INSERT INTO students (username, email, password, number, college_code, Reg, branch, semester, unique_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $name, $email, $password, $number, $college_code, $reg, $branch, $semester, $token);

    if ($stmt->execute()) {
                  // Data to be sent in the POST request
          $data = [
              'to' => $email,
              'studentName' => $name,
              'password' => $without_password,
              'collegeName' => $college_name
          ];

          // URL of the server-side script
          $url = 'https://service.insidemark.in/smartedu/student_template_email.php'; // Replace with your server URL

          // Initialize cURL session
          $ch = curl_init($url);

          // Set cURL options for POST request
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

          // Execute cURL session
          $response = curl_exec($ch);

          // Check for errors
          if ($response === false) {
              echo 'Error: ' . curl_error($ch);
          } else {
              $message = "Student member added successfully.";
          }

          // Close cURL session
          curl_close($ch);
    } else {
        $message = "Error adding student member.";
    }

    $stmt->close();
}

// Handle bulk upload of faculty members
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_upload'])) {
    $facultyData = json_decode($_POST['facultyData'], true);
    $errors = [];
    
    foreach ($facultyData as $faculty) {
        $name = $faculty['name'];
        $email = $faculty['email'];
        $without_password = $faculty['password'];
        $password = password_hash($faculty['password'], PASSWORD_BCRYPT);
        $number = $faculty['number'];
        $reg = $faculty['registration'];
      
      // Usage
$token = generateToken();

        $stmt = $conn->prepare("INSERT INTO students (username, email, password, number, college_code, Reg, branch, semester, unique_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $name, $email, $password, $number, $college_code, $reg, $branch, $semester, $token);

            if ($stmt->execute()) {
                                // Set the recipient email address
                        // Data to be sent in the POST request
          $data = [
              'to' => $email,
              'studentName' => $name,
              'password' => $without_password,
              'collegeName' => $college_name
          ];

          // URL of the server-side script
          $url = 'https://service.insidemark.in/smartedu/student_template_email.php'; // Replace with your server URL

          // Initialize cURL session
          $ch = curl_init($url);

          // Set cURL options for POST request
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

          // Execute cURL session
          $response = curl_exec($ch);

          // Check for errors
          if ($response === false) {
              echo 'Error: ' . curl_error($ch);
          } else {
              $message = "Student member added successfully.";
          }

          // Close cURL session
          curl_close($ch);
              
              
              
           
    } else {
        $message = "Error adding faculty member.";
    }
        $stmt->close();
    }

    if (empty($errors)) {
        $bulkMessage = "All Student members added successfully.";
        $message = "All Student members added successfully.";
    } else {
        $bulkMessage = implode("<br>", $errors);
    }
}
// Fetch all faculty members
$result = $conn->query("SELECT * FROM students WHERE college_code = '$college_code' AND semester = '$semester' AND branch = '$branch'");
?>

<?php include('header.php'); ?>

<main>
    <section class="manage-faculty-section">
        <div class="manage-faculty-container">
            <div class="manage-faculty-header">
                <h1>Manage Students</h1>
                <?php if (isset($message)) { echo "<p class='message'>$message</p>"; } ?>
                <?php if (isset($bulkMessage)) { echo "<p class='message'>$bulkMessage</p>"; } ?>
            </div>
            <div class="manage-faculty-body">
                <div class="add-faculty-form">
                    <h2>Add Student</h2>
                    <form action="" method="post">
                        <input type="text" name="name" placeholder="Name" required>
                        <input type="email" name="email" placeholder="Email" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <input type="text" name="number" placeholder="Phone Number" required>
                        <input type="text" name="reg" placeholder="Registration Number" required>
                        <button type="submit" name="add_faculty">Add Student</button>
                    </form>
                </div>
                <div class="bulk-upload-form">
                    <h2>Upload Students Data via Excel</h2>
                    <input type="file" id="excelFile" accept=".xls,.xlsx">
                    <label for="sheetNumber">Sheet Number:</label>
                    <input type="number" id="sheetNumber" min="1" value="1">
                    <button id="loadSheetButton">Load Sheet</button>
                    <div id="sheetDataContainer"></div>
                    <button id="uploadButton" style="display:none;">Upload</button>
                </div>
                  <!-- Upgrade Semester Section -->
                <div class="upgrade-semester-form">
                    <h2>Upgrade Student Semester</h2>
                    <form method="POST" action="">
                        <label for="current_semester">Selected Current Semester: <?php echo $semester ?></label>
                        <label for="current_semester">Selected Current Branch Code: <?php echo $branch ?></label>
                 
                        <button type="submit" name="upgrade_semester">Upgrade Semester</button>
                    </form>
                </div>
              
                <!-- Degrade Semester Section -->
                <div class="upgrade-semester-form">
                    <h2>Degrade Student Semester</h2>
                    <form method="POST" action="">
                        <label for="current_semester">Selected Current Semester: <?php echo $semester ?></label>
                        <label for="current_semester">Selected Current Branch Code: <?php echo $branch ?></label>
                 
                        <button type="submit" name="degrade_semester">Degrade Semester</button>
                    </form>
                </div>

                <div class="faculty-list">
                    <h2>Student Members</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['Reg']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['number']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>


<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.3/xlsx.full.min.js"></script>
<script>
document.getElementById('loadSheetButton').addEventListener('click', function () {
    var fileInput = document.getElementById('excelFile');
    var file = fileInput.files[0];
    var sheetNumber = parseInt(document.getElementById('sheetNumber').value) - 1;

    if (file) {
        // Check the file type
        var fileType = file.name.split('.').pop().toLowerCase();
        if (fileType === 'xlsx' || fileType === 'xls') {
            var reader = new FileReader();
            reader.onload = function (e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });
                var sheetNames = workbook.SheetNames;

                if (sheetNumber >= 0 && sheetNumber < sheetNames.length) {
                    var sheet = workbook.Sheets[sheetNames[sheetNumber]];
                    var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                    displaySheetData(jsonData);
                } else {
                    alert('Invalid sheet number.');
                }
            };
            reader.readAsArrayBuffer(file);
        } else {
            alert('Please select a valid Excel file.');
        }
    } else {
        alert('Please select an Excel file.');
    }
});


function displaySheetData(data) {
    var container = document.getElementById('sheetDataContainer');
    container.innerHTML = '';

    var table = document.createElement('table');
    var thead = document.createElement('thead');
    var tbody = document.createElement('tbody');

    data.forEach(function (row, rowIndex) {
        if (row.some(cell => cell)) { // Check if the row is not empty
            var tr = document.createElement('tr');
            row.forEach(function (cell) {
                var td = document.createElement(rowIndex === 0 ? 'th' : 'td');
                td.textContent = cell;
                tr.appendChild(td);
            });
            if (rowIndex === 0) {
                thead.appendChild(tr);
            } else {
                tbody.appendChild(tr);
            }
        }
    });

    table.appendChild(thead);
    table.appendChild(tbody);
    container.appendChild(table);

    var columnSelector = `
        <div>
            <label for="nameColumn">Name Column:</label>
            <input type="number" id="nameColumn" min="1" required>
            <label for="emailColumn">Email Column:</label>
            <input type="number" id="emailColumn" min="1" required>
            <label for="numberColumn">Phone Number Column:</label>
            <input type="number" id="numberColumn" min="1" required>
            <label for="RegistrationColumn">Registration Number Column:</label>
            <input type="number" id="registrationColumn" min="1" required>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', columnSelector);
    document.getElementById('uploadButton').style.display = 'block';
}

document.getElementById('uploadButton').addEventListener('click', function () {
    var table = document.querySelector('#sheetDataContainer table');
    var rows = table.querySelectorAll('tbody tr');
    var nameColumn = parseInt(document.getElementById('nameColumn').value) - 1;
    var emailColumn = parseInt(document.getElementById('emailColumn').value) - 1;
    var numberColumn = parseInt(document.getElementById('numberColumn').value) - 1;
    var registrationColumn = parseInt(document.getElementById('egistrationColumn').value) - 1;

    var facultyData = [];
    rows.forEach(function (row) {
        var cells = row.querySelectorAll('td');
        var name = cells[nameColumn].textContent;
        var email = cells[emailColumn].textContent;
        var number = cells[numberColumn].textContent;
        var registration = cells[registrationColumn].textContent;
        var password = Math.random().toString(36).slice(-8); // Generate a random 8-character password

        facultyData.push({ name: name, email: email, number: number, password: password, registration: registration });
    });

    var facultyDataJson = JSON.stringify(facultyData);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            alert('Faculty data uploaded successfully');
            location.reload();
        }
    };
    xhr.send('bulk_upload=1&facultyData=' + encodeURIComponent(facultyDataJson));
});

</script>

<style>
    .manage-faculty-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .manage-faculty-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .message {
        color: green;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .add-faculty-form, .bulk-upload-form {
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .add-faculty-form form input, .add-faculty-form form button,
    .bulk-upload-form input, .bulk-upload-form button {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: none;
        border-radius: 30px;
        font-size: 1em;
    }

    .add-faculty-form form button, .bulk-upload-form button {
        background: #388E3C;
        color: white;
        cursor: pointer;
        transition: background 0.3s;
    }

    .add-faculty-form form button:hover, .bulk-upload-form button:hover {
        background: #2e7031;
    }

    .faculty-list table {
        width: 100%;
        border-collapse: collapse;
    }

    .faculty-list table th, .faculty-list table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .faculty-list table th {
        background: #388E3C;
        color: white;
    }
    .upgrade-semester-form {
    background-color: #f9f9f9;
    padding: 20px;
    margin: 20px 0;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.upgrade-semester-form h2 {
    color: #333;
    border-bottom: 2px solid #333;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.upgrade-semester-form form {
    display: flex;
    flex-direction: column;
}

.upgrade-semester-form label {
    margin-bottom: 5px;
    color: #555;
}

.upgrade-semester-form select {
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
    transition: border-color 0.3s;
}

.upgrade-semester-form button {
    padding: 10px;
    background: #28a745;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.upgrade-semester-form button:hover {
    background: #218838;
}

</style>