<?php
require_once '../config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h2>Teacher Dashboard</h2>
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="create_quiz.php">Create Quiz</a></li>
                <li><a href="manage_quizzes.php">Manage Quizzes</a></li>
                <li><a href="view_results.php">View Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <main class="content">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Quizzes</h3>
                    <?php
                    $quiz_count = mysqli_fetch_assoc(mysqli_query($conn, 
                        "SELECT COUNT(*) as count FROM quizzes WHERE teacher_id = " . $_SESSION['user_id']))['count'];
                    ?>
                    <p class="stat-number"><?php echo $quiz_count; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <?php
                    $student_count = mysqli_fetch_assoc(mysqli_query($conn, 
                        "SELECT COUNT(DISTINCT student_id) as count FROM quiz_attempts 
                        WHERE quiz_id IN (SELECT id FROM quizzes WHERE teacher_id = " . $_SESSION['user_id'] . ")"))['count'];
                    ?>
                    <p class="stat-number"><?php echo $student_count; ?></p>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Recent Activity</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_attempts = mysqli_query($conn, 
                            "SELECT qa.*, q.title, u.username 
                            FROM quiz_attempts qa 
                            JOIN quizzes q ON qa.quiz_id = q.id 
                            JOIN users u ON qa.student_id = u.id 
                            WHERE q.teacher_id = " . $_SESSION['user_id'] . " 
                            ORDER BY qa.attempt_date DESC LIMIT 5");

                        while ($attempt = mysqli_fetch_assoc($recent_attempts)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($attempt['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($attempt['title']) . "</td>";
                            echo "<td>" . $attempt['score'] . "%</td>";
                            echo "<td>" . date('M d, Y', strtotime($attempt['attempt_date'])) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html> 