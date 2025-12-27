<?php
require_once '../config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Check if attempt ID is provided
if (!isset($_GET['attempt_id'])) {
    header('Location: my_results.php');
    exit();
}

$attempt_id = (int)$_GET['attempt_id'];

// Get attempt details with quiz info
$attempt_sql = "SELECT qa.*, q.title, q.description, q.passing_score, u.username as teacher_name
                FROM quiz_attempts qa
                JOIN quizzes q ON qa.quiz_id = q.id
                JOIN users u ON q.teacher_id = u.id
                WHERE qa.id = ? AND qa.student_id = ?";
$stmt = mysqli_prepare($conn, $attempt_sql);
mysqli_stmt_bind_param($stmt, "ii", $attempt_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$attempt = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$attempt) {
    header('Location: my_results.php');
    exit();
}

// Get detailed question and answer information
$questions_sql = "SELECT 
    q.question_text,
    q.points,
    sa.answer_id as student_answer_id,
    a.answer_text as student_answer_text,
    ca.id as correct_answer_id,
    ca.answer_text as correct_answer_text,
    (sa.answer_id = ca.id) as is_correct
FROM questions q
JOIN student_answers sa ON q.id = sa.question_id
JOIN answers a ON sa.answer_id = a.id
JOIN answers ca ON q.id = ca.question_id AND ca.is_correct = 1
WHERE sa.attempt_id = ?
ORDER BY q.id";

$stmt = mysqli_prepare($conn, $questions_sql);
mysqli_stmt_bind_param($stmt, "i", $attempt_id);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .results-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .quiz-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .quiz-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .info-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 20px;
            font-weight: bold;
            color: #1a73e8;
        }
        .info-value.passed {
            color: #28a745;
        }
        .info-value.failed {
            color: #dc3545;
        }
        .question-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .question-result {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .correct {
            background-color: #d4edda;
            color: #155724;
        }
        .incorrect {
            background-color: #f8d7da;
            color: #721c24;
        }
        .answer-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .answer-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        .answer-text {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .student-answer {
            background-color: #e8f0fe;
            border: 1px solid #1a73e8;
        }
        .correct-answer {
            background-color: #d4edda;
            border: 1px solid #28a745;
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
                <li><a href="my_results.php">My Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <main class="content">
            <div class="results-container">
                <div class="quiz-header">
                    <h1><?php echo htmlspecialchars($attempt['title']); ?></h1>
                    <p><?php echo htmlspecialchars($attempt['description']); ?></p>
                    
                    <div class="quiz-info">
                        <div class="info-item">
                            <div class="info-label">Score</div>
                            <div class="info-value <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'passed' : 'failed'; ?>">
                                <?php echo $attempt['score']; ?>%
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Time Taken</div>
                            <div class="info-value">
                                <?php 
                                $minutes = floor($attempt['time_taken'] / 60);
                                $seconds = $attempt['time_taken'] % 60;
                                echo sprintf("%d:%02d", $minutes, $seconds);
                                ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'passed' : 'failed'; ?>">
                                <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'Passed' : 'Failed'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Attempt Date</div>
                            <div class="info-value">
                                <?php echo date('M j, Y g:i A', strtotime($attempt['attempt_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php 
                $question_number = 1;
                while ($question = mysqli_fetch_assoc($questions)): 
                ?>
                <div class="question-card">
                    <div class="question-header">
                        <h3>Question <?php echo $question_number++; ?></h3>
                        <span class="question-result <?php echo $question['is_correct'] ? 'correct' : 'incorrect'; ?>">
                            <?php echo $question['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                        </span>
                    </div>
                    
                    <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                    
                    <div class="answer-section">
                        <div class="answer-label">Your Answer:</div>
                        <div class="answer-text student-answer">
                            <?php echo htmlspecialchars($question['student_answer_text']); ?>
                        </div>
                        
                        <?php if (!$question['is_correct']): ?>
                        <div class="answer-label">Correct Answer:</div>
                        <div class="answer-text correct-answer">
                            <?php echo htmlspecialchars($question['correct_answer_text']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
</body>
</html> 