<?php
require_once '../config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}

// Handle quiz deletion
if (isset($_POST['delete_quiz'])) {
    $quiz_id = (int)$_POST['quiz_id'];
    $delete_sql = "DELETE FROM quizzes WHERE id = ? AND teacher_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .quiz-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .quiz-title {
            margin: 0;
            color: #1a73e8;
        }
        .quiz-actions {
            display: flex;
            gap: 10px;
        }
        .btn-edit, .btn-view, .btn-delete {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-view {
            background-color: #1a73e8;
        }
        .btn-edit {
            background-color: #ffc107;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .quiz-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1a73e8;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h2>Teacher Dashboard</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_quiz.php">Create Quiz</a></li>
                <li><a href="manage_quizzes.php" class="active">Manage Quizzes</a></li>
                <li><a href="view_results.php">View Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <main class="content">
            <h1>Manage Quizzes</h1>

            <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                Quiz has been successfully created!
            </div>
            <?php endif; ?>

            <?php
            $quizzes = mysqli_query($conn, 
                "SELECT q.*, 
                    (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
                    (SELECT AVG(score) FROM quiz_attempts WHERE quiz_id = q.id) as avg_score,
                    (SELECT COUNT(DISTINCT student_id) FROM quiz_attempts WHERE quiz_id = q.id) as student_count
                FROM quizzes q 
                WHERE q.teacher_id = " . $_SESSION['user_id'] . " 
                ORDER BY q.created_at DESC");

            while ($quiz = mysqli_fetch_assoc($quizzes)):
            ?>
            <div class="quiz-card">
                <div class="quiz-header">
                    <h3 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <div class="quiz-actions">
                        <a href="view_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-view">View Details</a>
                        <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-edit">Edit</a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this quiz?');">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                            <button type="submit" name="delete_quiz" class="btn-delete">Delete</button>
                        </form>
                    </div>
                </div>
                <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                <div class="quiz-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $quiz['attempt_count']; ?></div>
                        <div class="stat-label">Total Attempts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $quiz['student_count']; ?></div>
                        <div class="stat-label">Students Taken</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($quiz['avg_score'] ?? 0, 1); ?>%</div>
                        <div class="stat-label">Average Score</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $quiz['time_limit']; ?> min</div>
                        <div class="stat-label">Time Limit</div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if (mysqli_num_rows($quizzes) == 0): ?>
            <div class="quiz-card" style="text-align: center;">
                <h3>No Quizzes Created Yet</h3>
                <p>Start by creating your first quiz!</p>
                <a href="create_quiz.php" class="btn-view" style="display: inline-block; margin-top: 10px;">Create Quiz</a>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 