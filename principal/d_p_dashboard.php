<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #388E3C;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        .logout-button {
            position: absolute;
            right: 15px;
            top: 15px;
            background: #d32f2f;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        .container {
            display: flex;
        }
        .sidebar {
            background: #333;
            color: white;
            width: 250px;
            height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
            margin: 10px 0;
        }
        .sidebar a:hover {
            background: #575757;
        }
        .main {
            flex: 1;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-top: 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px;
            border: 1px solid #ddd;
        }
        .table th {
            background: #f4f4f4;
        }
        .form-control {
            width: calc(100% - 24px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .button {
            padding: 10px 20px;
            background: #388E3C;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button:hover {
            background: #2e7031;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
</head>
<body>
    <div class="header">
        <h1>Principal Dashboard</h1>
        <button class="logout-button" id="logoutButton">Logout</button>
    </div>
    <div class="container">
        <div class="sidebar">
            <a href="#view-profile">Profile</a>
            <a href="#view-faculty">View Faculty</a>
            <a href="#view-students">View Students</a>
            <a href="#add-user">Add User</a>
            <a href="#upload-excel">Upload Excel</a>
        </div>
        <div class="main">
            <div id="view-profile" class="card">
                <h2>Profile</h2>
                <p><strong>Name:</strong> John Doe</p>
                <p><strong>Email:</strong> john.doe@example.com</p>
                <p><strong>Role:</strong> Principal</p>
            </div>
            <div id="view-faculty" class="card">
                <h2>View Faculty</h2>
                <table class="table" id="facultyTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Faculty data will be inserted here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="view-students" class="card">
                <h2>View Students</h2>
                <table class="table" id="studentTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Student data will be inserted here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="add-user" class="card">
                <h2>Add User</h2>
                <form id="addUserForm">
                    <input type="text" class="form-control" id="name" placeholder="Name" required>
                    <input type
                    ="email" class="form-control" id="email" placeholder="Email" required>
                    <input type="password" class="form-control" id="password" placeholder="Password" required>
                    <select class="form-control" id="role" required>
                        <option value="faculty">Faculty</option>
                        <option value="student">Student</option>
                    </select>
                    <button type="submit" class="button">Add User</button>
                </form>
                <p id="addUserMessage"></p>
            </div>
            <div id="upload-excel" class="card">
                <h2>Upload Excel</h2>
                <input type="file" class="form-control" id="excelFile" accept=".xlsx, .xls">
                <button class="button" id="uploadButton">Upload</button>
                <p id="uploadMessage"></p>
            </div>
        </div>
    </div>
    <script>
        // Sample data
        const faculty = [
            { id: 1, name: 'Alice Johnson', email: 'alice@example.com' },
            { id: 2, name: 'Bob Smith', email: 'bob@example.com' }
        ];

        const students = [
            { id: 1, name: 'Charlie Brown', email: 'charlie@example.com' },
            { id: 2, name: 'Daisy Ridley', email: 'daisy@example.com' }
        ];

        function loadTableData(tableId, data) {
            const tableBody = document.getElementById(tableId).getElementsByTagName('tbody')[0];
            tableBody.innerHTML = '';
            data.forEach((item, index) => {
                const row = tableBody.insertRow();
                row.insertCell(0).innerText = item.id;
                row.insertCell(1).innerText = item.name;
                row.insertCell(2).innerText = item.email;
                const actionCell = row.insertCell(3);
                const deleteButton = document.createElement('button');
                deleteButton.innerText = 'Delete';
                deleteButton.className = 'button';
                deleteButton.onclick = () => {
                    if (tableId === 'facultyTable') {
                        faculty.splice(index, 1);
                    } else {
                        students.splice(index, 1);
                    }
                    loadTableData(tableId, tableId === 'facultyTable' ? faculty : students);
                };
                actionCell.appendChild(deleteButton);
            });
        }

        document.getElementById('addUserForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;

            const newUser = {
                id: role === 'faculty' ? faculty.length + 1 : students.length + 1,
                name: name,
                email: email
            };

            if (role === 'faculty') {
                faculty.push(newUser);
                loadTableData('facultyTable', faculty);
            } else {
                students.push(newUser);
                loadTableData('studentTable', students);
            }

            document.getElementById('addUserForm').reset();
            document.getElementById('addUserMessage').innerText = 'User added successfully!';
            setTimeout(() => {
                document.getElementById('addUserMessage').innerText = '';
            }, 3000);
        });

        document.getElementById('uploadButton').addEventListener('click', function() {
            const fileInput = document.getElementById('excelFile');
            const file = fileInput.files[0];
            if (!file) {
                document.getElementById('uploadMessage').innerText = 'Please select a file.';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(event) {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const sheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[sheetName];
                const json = XLSX.utils.sheet_to_json(worksheet);

                json.forEach(user => {
                    const newUser = {
                        id: user.role === 'faculty' ? faculty.length + 1 : students.length + 1,
                        name: user.name,
                        email: user.email
                    };

                    if (user.role === 'faculty') {
                        faculty.push(newUser);
                    } else {
                        students.push(newUser);
                    }
                });

                loadTableData('facultyTable', faculty);
                loadTableData('studentTable', students);

                document.getElementById('uploadMessage').innerText = 'File uploaded successfully!';
                fileInput.value = '';
                setTimeout(() => {
                    document.getElementById('uploadMessage').innerText = '';
                }, 3000);
            };
            reader.readAsArrayBuffer(file);
        });

        document.getElementById('logoutButton').addEventListener('click', function() {
            // Handle logout (e.g., redirect to login page, clear session)
            alert('Logged out');
            window.location.href = 'login.php';
        });

        // Initialize tables with sample data
        loadTableData('facultyTable', faculty);
        loadTableData('studentTable', students);
    </script>
</body>
</html>
