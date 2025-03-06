<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovative Attendance Management App - Inside Mark</title>
    <link rel="icon" href="principal/img/logo.png" type="image/x-icon">
    <meta name="description" content="Inside Mark provides a user-friendly attendance management app for educational institutions. Faculty can easily take attendance, students can view their records, and principals can monitor specific classes and dates.">
    <meta name="keywords" content="Attendance Management, Educational Institutions, Faculty, Students, Principals, Attendance Records">
    <meta name="author" content="Nitin Kumar">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(45deg, #24DDF4, #388E3C);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }

        .title {
            font-size: 32px;
            color: #333333;
            margin-bottom: 24px;
            font-weight: bold;
        }

        .button-group {
            width: 100%;
        }

        .custom-button {
            width: 100%;
            height: 60px;
            margin: 10px 0;
            background-color: #24DDF4;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            border: none;
            cursor: pointer;
            text-align: left;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, transform 0.3s;
        }

        .custom-button .button-icon {
            width: 24px;
            height: 24px;
            margin-right: 20px;
        }

        .custom-button:hover {
            background-color: #1A8CD8;
            transform: scale(1.05);
        }

        .custom-button:focus {
            outline: none;
        }

        .custom-button:active {
            background-color: #157BA6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">Continue As</h1>
        <div class="button-group">
            <button class="custom-button" id="studentButton">
                <img src="assets/img/ic_student.png" alt="Student Icon" class="button-icon">Student
            </button>
            <button class="custom-button" id="facultyButton">
                <img src="assets/img/ic_faculty.png" alt="Faculty Icon" class="button-icon">Faculty
            </button>
            <button class="custom-button" id="principalButton">
                <img src="assets/img/ic_college.png" alt="Principal Icon" class="button-icon">Principal
            </button>
            <button class="custom-button" id="othersButton">
                <img src="assets/img/ic_other.png" alt="Others Icon" class="button-icon">Others
            </button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("studentButton").addEventListener("click", function() {
                window.location.href = "auth/login.php?role=students";
            });
            document.getElementById("facultyButton").addEventListener("click", function() {
                window.location.href = "auth/login.php?role=faculty";
            });
            document.getElementById("principalButton").addEventListener("click", function() {
                window.location.href = "auth/login.php?role=principal";
            });
            document.getElementById("othersButton").addEventListener("click", function() {
                alert("Currently we are not allowing any unknown person.");
            });
        });
    </script>
</body>
</html>
