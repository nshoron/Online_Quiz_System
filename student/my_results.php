<?php
require_once '../config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Get all quiz attempts by the student
$attempts_sql = "SELECT 
    qa.id as attempt_id,
    q.title,
    q.description,
    q.passing_score,
    qa.score,
    qa.time_taken,
    qa.attempt_date,
    u.username as teacher_name,
    (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as total_questions,
    (SELECT COUNT(*) 
     FROM student_answers sa 
     JOIN answers a ON sa.answer_id = a.id 
     WHERE sa.attempt_id = qa.id AND a.is_correct = 1) as correct_answers
FROM quiz_attempts qa
JOIN quizzes q ON qa.quiz_id = q.id
JOIN users u ON q.teacher_id = u.id
WHERE qa.student_id = ?
ORDER BY qa.attempt_date DESC";

$stmt = mysqli_prepare($conn, $attempts_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$attempts = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .results-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .attempt-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .attempt-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .attempt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .quiz-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a73e8;
            margin: 0;
        }
        .attempt-date {
            color: #666;
            font-size: 14px;
        }
        .attempt-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1a73e8;
        }
        .stat-value.passed {
            color: #28a745;
        }
        .stat-value.failed {
            color: #dc3545;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .view-details {
            display: inline-block;
            background-color: #1a73e8;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .view-details:hover {
            background-color: #1557b0;
            text-decoration: none;
            color: white;
        }
        .no-attempts {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #1a73e8;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .progress-fill.passed {
            background-color: #28a745;
        }
        .progress-fill.failed {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h2>Student Dashboard</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="available_quizzes.php">Available Quizzes</a></li>
                <li><a href="my_results.php" class="active">My Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <main class="content">
            <div class="results-container">
                <h1>My Quiz Results</h1>

                <?php if (mysqli_num_rows($attempts) > 0): ?>
                    <?php while ($attempt = mysqli_fetch_assoc($attempts)): ?>
                    <div class="attempt-card">
                        <div class="attempt-header">
                            <h3 class="quiz-title"><?php echo htmlspecialchars($attempt['title']); ?></h3>
                            <span class="attempt-date">
                                <?php echo date('M j, Y g:i A', strtotime($attempt['attempt_date'])); ?>
                            </span>
                        </div>

                        <div class="attempt-stats">
                            <div class="stat-item">
                                <div class="stat-value <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'passed' : 'failed'; ?>">
                                    <?php echo $attempt['score']; ?>%
                                </div>
                                <div class="stat-label">Score</div>
                                <div class="progress-bar">
                                    <div class="progress-fill <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'passed' : 'failed'; ?>" 
                                         style="width: <?php echo $attempt['score']; ?>%"></div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">
                                    <?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?>
                                </div>
                                <div class="stat-label">Correct Answers</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">
                                    <?php 
                                    $minutes = floor($attempt['time_taken'] / 60);
                                    $seconds = $attempt['time_taken'] % 60;
                                    echo sprintf("%d:%02d", $minutes, $seconds);
                                    ?>
                                </div>
                                <div class="stat-label">Time Taken</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'passed' : 'failed'; ?>">
                                    <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'Passed' : 'Failed'; ?>
                                </div>
                                <div class="stat-label">Status</div>
                            </div>
                        </div>

                        <p><?php echo htmlspecialchars($attempt['description']); ?></p>
                        <a href="quiz_results.php?attempt_id=<?php echo $attempt['attempt_id']; ?>" class="view-details">
                            View Details
                        </a>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-attempts">
                        <h2>No Quiz Attempts Yet</h2>
                        <p>You haven't taken any quizzes yet. Start by taking a quiz from the Available Quizzes section!</p>
                        <a href="available_quizzes.php" class="view-details">View Available Quizzes</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 