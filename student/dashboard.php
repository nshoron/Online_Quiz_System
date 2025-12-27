<?php
require_once '../config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Get total quizzes taken
$total_quizzes_sql = "SELECT COUNT(*) as total FROM quiz_attempts WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $total_quizzes_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$total_quizzes = mysqli_stmt_get_result($stmt)->fetch_assoc()['total'];

// Get average score
$avg_score_sql = "SELECT AVG(score) as average FROM quiz_attempts WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $avg_score_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$avg_score = mysqli_stmt_get_result($stmt)->fetch_assoc()['average'];

// Get recent attempts
$recent_sql = "SELECT qa.*, q.title, q.passing_score 
              FROM quiz_attempts qa 
              JOIN quizzes q ON qa.quiz_id = q.id 
              WHERE qa.student_id = ? 
              ORDER BY qa.attempt_date DESC 
              LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$recent_attempts = mysqli_stmt_get_result($stmt);

// Get available quizzes
$available_sql = "SELECT COUNT(*) as count 
                 FROM quizzes q 
                 WHERE NOT EXISTS (
                     SELECT 1 FROM quiz_attempts qa 
                     WHERE qa.quiz_id = q.id AND qa.student_id = ?
                 )";
$stmt = mysqli_prepare($conn, $available_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$available_quizzes = mysqli_stmt_get_result($stmt)->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Quiz System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f6f8;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
        }
        .sidebar h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #34495e;
        }
        .sidebar ul {
            list-style: none;
        }
        .sidebar ul li {
            margin-bottom: 10px;
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #34495e;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .welcome-box {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .recent-attempts {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .attempts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .attempts-table th,
        .attempts-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .attempts-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .score-passed {
            color: #28a745;
            font-weight: bold;
        }
        .score-failed {
            color: #dc3545;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Student Dashboard</h2>
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="available_quizzes.php">Available Quizzes</a></li>
                <li><a href="my_results.php">My Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="welcome-box">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p>Here's your quiz performance overview.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Total Quizzes Taken</h3>
                    <div class="stat-number"><?php echo $total_quizzes; ?></div>
                </div>
                <div class="stat-box">
                    <h3>Average Score</h3>
                    <div class="stat-number"><?php echo $avg_score ? number_format($avg_score, 1) : '0'; ?>%</div>
                </div>
                <div class="stat-box">
                    <h3>Available Quizzes</h3>
                    <div class="stat-number"><?php echo $available_quizzes; ?></div>
                </div>
            </div>

            <div class="recent-attempts">
                <h2>Recent Attempts</h2>
                <?php if (mysqli_num_rows($recent_attempts) > 0): ?>
                    <table class="attempts-table">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($attempt = mysqli_fetch_assoc($recent_attempts)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attempt['title']); ?></td>
                                    <td><?php echo $attempt['score']; ?>%</td>
                                    <td><?php echo date('M j, Y, g:i a', strtotime($attempt['attempt_date'])); ?></td>
                                    <td class="<?php echo $attempt['score'] >= $attempt['passing_score'] ? 'score-passed' : 'score-failed'; ?>">
                                        <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'Passed' : 'Failed'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No quiz attempts yet.</p>
                    <p><a href="available_quizzes.php" class="btn">Take Your First Quiz</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 