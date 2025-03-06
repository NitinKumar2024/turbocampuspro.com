<?php
session_start();
require_once '../config.php';


// Logout functionality
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../');
    exit();
}
?>

<?php include('header.php'); ?>

<main>
    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-header">
                <h1>Profile</h1>
            </div>
            <div class="profile-body">
                <div class="profile-picture">
                    <img src="../assets/img/ic_college.png" alt="Profile Picture">
                </div>
                <div class="profile-details">
                    <div class="detail">
                        <h2>Name</h2>
                        <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    <div class="detail">
                        <h2>Email</h2>
                        <p><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    </div>
                    <div class="detail">
                        <h2>College Name</h2>
                        <p><?php echo htmlspecialchars($_SESSION['college_name']); ?></p>
                    </div>
                    <div class="detail">
                        <h2>College Code</h2>
                        <p><?php echo htmlspecialchars($_SESSION['college_code']); ?></p>
                    </div>
                    <div class="detail">
                        <h2>Phone Number</h2>
                        <p><?php echo htmlspecialchars($_SESSION['number']); ?></p>
                    </div>
                </div>
            </div>
           <!-- Logout form -->
<form id="logoutForm" method="post">
    <input type="hidden" name="logout" value="1">
    <button type="submit">Logout</button>
</form>

<script>
    document.getElementById('logoutForm').addEventListener('submit', function(event) {
        var isConfirmed = confirm('Are you sure you want to logout?');
        if (!isConfirmed) {
            event.preventDefault(); // Prevent form submission if user cancels
        }
    });
</script>

        </div>
    </section>
</main>



<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f8f8f8;
        margin: 0;
        padding: 0;
    }
    main {
        padding: 20px;
    }
    .profile-section {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
    }
    .profile-container {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        width: 100%;
    }
    .profile-header h1 {
        margin-top: 0;
        color: #388E3C;
        font-size: 36px;
    }
    .profile-body {
        display: flex;
        align-items: center;
        margin-top: 20px;
    }
    .profile-picture img {
        border-radius: 50%;
        width: 150px;
        height: 150px;
        object-fit: cover;
        margin-right: 20px;
    }
    .profile-details {
        flex: 1;
    }
    .detail {
        margin-bottom: 20px;
    }
    .detail h2 {
        margin: 0;
        font-size: 20px;
        color: #555;
    }
    .detail p {
        margin: 5px 0 0;
        font-size: 16px;
        color: #777;
    }
    footer {
        text-align: center;
        padding: 10px;
        background: #388E3C;
        color: white;
        position: fixed;
        bottom: 0;
        width: 100%;
    }
    
    button {
        padding: 10px;
        background: #28a745;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover {
        background: #218838;
    }

</style>
