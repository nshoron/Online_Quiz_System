<?php
require_once '../config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Check if quiz ID is provided
if (!isset($_GET['quiz_id'])) {
    header('Location: available_quizzes.php');
    exit();
}

$quiz_id = (int)$_GET['quiz_id'];

// Get quiz details
$quiz_sql = "SELECT * FROM quizzes WHERE id = ?";
$stmt = mysqli_prepare($conn, $quiz_sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$quiz = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$quiz) {
    header('Location: available_quizzes.php');
    exit();
}

// Check if student has already attempted this quiz
$attempt_check_sql = "SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?";
$stmt = mysqli_prepare($conn, $attempt_check_sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$existing_attempt = mysqli_stmt_get_result($stmt)->fetch_assoc();

if ($existing_attempt) {
    header('Location: quiz_results.php?attempt_id=' . $existing_attempt['id']);
    exit();
}

// Get questions
$questions_sql = "SELECT q.*, GROUP_CONCAT(
                    CONCAT(a.id, ':', a.answer_text)
                    ORDER BY a.id
                    SEPARATOR '||'
                ) as answers
                FROM questions q
                LEFT JOIN answers a ON q.id = a.question_id
                WHERE q.quiz_id = ?
                GROUP BY q.id
                ORDER BY q.id";
$stmt = mysqli_prepare($conn, $questions_sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$questions_result = mysqli_stmt_get_result($stmt);
$questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $answers = [];
    foreach (explode('||', $row['answers']) as $answer) {
        list($id, $text) = explode(':', $answer);
        $answers[] = ['id' => $id, 'text' => $text];
    }
    $row['answer_options'] = $answers;
    unset($row['answers']);
    $questions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .quiz-container {
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
        .timer {
            font-size: 24px;
            font-weight: bold;
            color: #1a73e8;
            text-align: center;
            margin: 10px 0;
        }
        .question-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .answer-option {
            display: block;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .answer-option:hover {
            background-color: #f8f9fa;
            border-color: #1a73e8;
        }
        .answer-option input[type="radio"] {
            margin-right: 10px;
        }
        .submit-btn {
            background-color: #1a73e8;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        .submit-btn:hover {
            background-color: #1557b0;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        .answer-option.selected {
            background-color: #e8f0fe;
            border-color: #1a73e8;
        }
        .question-card.unanswered {
            border: 2px solid #dc3545;
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
            <div class="quiz-container">
                <div class="quiz-header">
                    <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
                    <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                    <div class="timer" id="timer"></div>
                </div>

                <div id="error-message" class="error-message">
                    Please answer all questions before submitting.
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message" style="display: block;">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                </div>
                <?php endif; ?>

                <form id="quiz-form" method="POST" action="process_quiz_attempt.php" onsubmit="return validateForm()">
                    <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                    <input type="hidden" name="start_time" value="<?php echo time(); ?>">
                    
                    <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card" id="question-<?php echo $question['id']; ?>">
                        <h3>Question <?php echo $index + 1; ?></h3>
                        <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                        
                        <?php foreach ($question['answer_options'] as $answer): ?>
                        <label class="answer-option">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" 
                                   value="<?php echo $answer['id']; ?>" 
                                   onchange="handleAnswerSelection(this)">
                            <?php echo htmlspecialchars($answer['text']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                    <button type="submit" class="submit-btn" id="submit-btn">Submit Quiz</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Timer functionality
        const timeLimit = <?php echo $quiz['time_limit']; ?> * 60; // Convert to seconds
        let timeLeft = timeLimit;
        const timerElement = document.getElementById('timer');
        const quizForm = document.getElementById('quiz-form');
        const errorMessage = document.getElementById('error-message');
        let isSubmitting = false;

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `Time Left: ${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft <= 0) {
                submitForm(true);
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        function handleAnswerSelection(input) {
            // Remove selected class from all options in this question group
            const questionCard = input.closest('.question-card');
            const options = questionCard.querySelectorAll('.answer-option');
            options.forEach(option => option.classList.remove('selected'));
            
            // Add selected class to the chosen option
            input.closest('.answer-option').classList.add('selected');
            
            // Remove unanswered class from the question card
            questionCard.classList.remove('unanswered');
            
            // Hide error message
            errorMessage.style.display = 'none';
        }

        function validateForm() {
            if (isSubmitting) {
                return true;
            }

            const questions = document.querySelectorAll('.question-card');
            let allAnswered = true;
            let formData = new FormData(quizForm);
            
            questions.forEach(question => {
                const questionId = question.id.replace('question-', '');
                const answered = question.querySelector('input[type="radio"]:checked');
                if (!answered) {
                    question.classList.add('unanswered');
                    allAnswered = false;
                }
            });

            if (!allAnswered) {
                errorMessage.style.display = 'block';
                errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return false;
            }

            // Debug log the form data
            console.log('Submitting form with data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            return submitForm(false);
        }

        function submitForm(isTimeout) {
            if (isSubmitting) {
                return true;
            }

            // Double check all answers are selected
            const questions = document.querySelectorAll('.question-card');
            let answers = {};
            let allAnswered = true;

            questions.forEach(question => {
                const questionId = question.id.replace('question-', '');
                const selectedAnswer = question.querySelector('input[type="radio"]:checked');
                if (selectedAnswer) {
                    answers[questionId] = selectedAnswer.value;
                } else {
                    allAnswered = false;
                }
            });

            if (!allAnswered && !isTimeout) {
                errorMessage.textContent = 'Please answer all questions before submitting.';
                errorMessage.style.display = 'block';
                errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return false;
            }

            isSubmitting = true;
            
            // Remove the page unload warning
            window.onbeforeunload = null;
            
            // If it's a timeout submission, show a message
            if (isTimeout) {
                alert('Time is up! Your answers will be submitted automatically.');
            }
            
            // Submit the form
            try {
                quizForm.submit();
            } catch (error) {
                console.error('Error submitting form:', error);
                isSubmitting = false;
                return false;
            }
            return true;
        }

        // Initialize timer
        updateTimer();

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Warn user before leaving page
        window.onbeforeunload = function(e) {
            if (!isSubmitting) {
                e.preventDefault();
                return "Are you sure you want to leave? Your quiz progress will be lost.";
            }
        };

        // Handle browser back button
        window.addEventListener('popstate', function(e) {
            if (!isSubmitting) {
                e.preventDefault();
                if (confirm('Are you sure you want to leave? Your quiz progress will be lost.')) {
                    window.location.href = 'available_quizzes.php';
                }
            }
        });

        // Add form submission debugging
        quizForm.addEventListener('submit', function(e) {
            console.log('Form submission triggered');
            const formData = new FormData(this);
            console.log('Form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
        });
    </script>
</body>
</html> 