<?php
// Start session and include necessary files
session_start();
include('header.php');
require_once '../config.php';

// Fetch all branches from the database
$query = "SELECT * FROM branch WHERE college_code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['college_code']);
$stmt->execute();
$result = $stmt->get_result();
$branches = [];
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branches</title>
  
    <style>
        .branch-section {
            padding: 20px;
            text-align: center;
        }

        .branch-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .branch-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 300px;
            transition: transform 0.3s;
        }

        .branch-card:hover {
            transform: scale(1.05);
        }

        .branch-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .branch-card h3 {
            margin: 10px 0;
            color: #333;
        }

        .branch-card p {
            padding: 0 10px;
            color: #666;
            height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .semesters {
            padding: 10px;
            background: #f9f9f9;
            border-top: 1px solid #ddd;
        }

        .semesters h4 {
            margin: 0;
            color: #007bff;
        }

        .semesters ul {
            list-style: none;
            padding: 0;
            margin: 5px 0;
        }

        .semesters li {
            margin: 5px 0;
        }

        .btn {
            display: inline-block;
            margin: 10px;
            padding: 10px 15px;
            background: #007bff;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        footer {
            text-align: center;
            padding: 10px;
            background: #f1f1f1;
            position: absolute;
            width: 100%;
            bottom: 0;
        }
    </style>
</head>
<body>
<main>
    <section class="branch-section">
        <h2>Manage Branches</h2>
        <div class="branch-container">
            <?php foreach ($branches as $branch): ?>
                <div class="branch-card">
                    <img src="<?php echo htmlspecialchars($branch['branch_image']); ?>" alt="<?php echo htmlspecialchars($branch['branch_name']); ?>">
                    <h3><?php echo htmlspecialchars($branch['branch_name']); ?></h3>
                    <p><?php echo htmlspecialchars($branch['branch_description']); ?></p>
                    <div class="semesters">
                        <h4>Semesters</h4>
                        <ul>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <li><a href="manage-students.php?branch_code=<?php echo $branch['branch_code']; ?>&semester=<?php echo $i; ?>" class="btn">Semester <?php echo $i; ?></a></li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

</body>
</html>
