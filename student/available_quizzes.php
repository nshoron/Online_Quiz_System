<?php
require_once '../config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Get available quizzes (not attempted by the student)
$quizzes_sql = "SELECT q.*, u.username as teacher_name,
                (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                FROM quizzes q
                JOIN users u ON q.teacher_id = u.id
                WHERE q.id NOT IN (
                    SELECT quiz_id 
                    FROM quiz_attempts 
                    WHERE student_id = ?
                )
                ORDER BY q.created_at DESC";

$stmt = mysqli_prepare($conn, $quizzes_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$quizzes = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Quizzes - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }
        .quiz-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.2s;
        }
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .quiz-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a73e8;
            margin-bottom: 10px;
        }
        .quiz-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .quiz-description {
            color: #444;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .take-quiz-btn {
            display: inline-block;
            background-color: #1a73e8;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .take-quiz-btn:hover {
            background-color: #1557b0;
            text-decoration: none;
            color: white;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .no-quizzes {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h2>Student Dashboard</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="available_quizzes.php" class="active">Available Quizzes</a></li>
                <li><a href="my_results.php">My Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <main class="content">
            <h1>Available Quizzes</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($quizzes) > 0): ?>
            <div class="quiz-grid">
                <?php while ($quiz = mysqli_fetch_assoc($quizzes)): ?>
                <div class="quiz-card">
                    <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
                    <div class="quiz-info">
                        <p>Created by: <?php echo htmlspecialchars($quiz['teacher_name']); ?></p>
                        <p>Questions: <?php echo $quiz['question_count']; ?></p>
                        <p>Time Limit: <?php echo $quiz['time_limit']; ?> minutes</p>
                        <p>Passing Score: <?php echo $quiz['passing_score']; ?>%</p>
                    </div>
                    <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                    <a href="take_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="take-quiz-btn">Take Quiz</a>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-quizzes">
                <h2>No Available Quizzes</h2>
                <p>You have completed all available quizzes. Check back later for new quizzes!</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 