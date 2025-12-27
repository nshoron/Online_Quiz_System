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
    <title>Create Quiz - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .quiz-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .question-block {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .answer-block {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .btn-add {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .correct-answer {
            color: #28a745;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h2>Teacher Dashboard</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_quiz.php" class="active">Create Quiz</a></li>
                <li><a href="manage_quizzes.php">Manage Quizzes</a></li>
                <li><a href="view_results.php">View Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <main class="content">
            <h1>Create New Quiz</h1>
            
            <form class="quiz-form" action="process_quiz.php" method="POST">
                <div class="form-group">
                    <label for="title">Quiz Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="time_limit">Time Limit (minutes):</label>
                    <input type="number" id="time_limit" name="time_limit" min="5" max="180" value="30" required>
                </div>

                <div class="form-group">
                    <label for="passing_score">Passing Score (%):</label>
                    <input type="number" id="passing_score" name="passing_score" min="0" max="100" value="70" required>
                </div>

                <div id="questions-container">
                    <h3>Questions</h3>
                    <div class="question-block">
                        <div class="form-group">
                            <label>Question 1:</label>
                            <input type="text" name="questions[0][text]" required>
                            <select name="questions[0][type]" onchange="updateAnswerType(this, 0)">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                            </select>
                        </div>
                        <div class="answers" id="answers-0">
                            <div class="answer-block">
                                <input type="text" name="questions[0][answers][0][text]" placeholder="Answer 1" required>
                                <input type="radio" name="questions[0][correct]" value="0" required>
                                <span class="correct-answer">Correct Answer</span>
                            </div>
                            <div class="answer-block">
                                <input type="text" name="questions[0][answers][1][text]" placeholder="Answer 2" required>
                                <input type="radio" name="questions[0][correct]" value="1">
                                <span class="correct-answer">Correct Answer</span>
                            </div>
                        </div>
                        <button type="button" class="btn-add" onclick="addAnswer(0)">Add Answer</button>
                    </div>
                </div>
                
                <button type="button" class="btn-add" style="margin: 20px 0;" onclick="addQuestion()">Add Question</button>
                <button type="submit" style="margin-left: 10px;">Create Quiz</button>
            </form>
        </main>
    </div>

    <script>
        let questionCount = 1;
        let answerCounts = [2];

        function addQuestion() {
            const container = document.getElementById('questions-container');
            const questionBlock = document.createElement('div');
            questionBlock.className = 'question-block';
            
            answerCounts[questionCount] = 2;
            
            questionBlock.innerHTML = `
                <div class="form-group">
                    <label>Question ${questionCount + 1}:</label>
                    <input type="text" name="questions[${questionCount}][text]" required>
                    <select name="questions[${questionCount}][type]" onchange="updateAnswerType(this, ${questionCount})">
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="true_false">True/False</option>
                    </select>
                    <button type="button" class="btn-remove" onclick="removeQuestion(this)">Remove Question</button>
                </div>
                <div class="answers" id="answers-${questionCount}">
                    <div class="answer-block">
                        <input type="text" name="questions[${questionCount}][answers][0][text]" placeholder="Answer 1" required>
                        <input type="radio" name="questions[${questionCount}][correct]" value="0" required>
                        <span class="correct-answer">Correct Answer</span>
                    </div>
                    <div class="answer-block">
                        <input type="text" name="questions[${questionCount}][answers][1][text]" placeholder="Answer 2" required>
                        <input type="radio" name="questions[${questionCount}][correct]" value="1">
                        <span class="correct-answer">Correct Answer</span>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addAnswer(${questionCount})">Add Answer</button>
            `;
            
            container.appendChild(questionBlock);
            questionCount++;
        }

        function addAnswer(questionIndex) {
            const answersContainer = document.getElementById(`answers-${questionIndex}`);
            const answerBlock = document.createElement('div');
            answerBlock.className = 'answer-block';
            
            answerBlock.innerHTML = `
                <input type="text" name="questions[${questionIndex}][answers][${answerCounts[questionIndex]}][text]" placeholder="Answer ${answerCounts[questionIndex] + 1}" required>
                <input type="radio" name="questions[${questionIndex}][correct]" value="${answerCounts[questionIndex]}">
                <span class="correct-answer">Correct Answer</span>
                <button type="button" class="btn-remove" onclick="removeAnswer(this)">Remove</button>
            `;
            
            answersContainer.appendChild(answerBlock);
            answerCounts[questionIndex]++;
        }

        function removeQuestion(button) {
            button.closest('.question-block').remove();
        }

        function removeAnswer(button) {
            button.closest('.answer-block').remove();
        }

        function updateAnswerType(select, questionIndex) {
            const answersContainer = document.getElementById(`answers-${questionIndex}`);
            if (select.value === 'true_false') {
                answersContainer.innerHTML = `
                    <div class="answer-block">
                        <input type="text" name="questions[${questionIndex}][answers][0][text]" value="True" readonly required>
                        <input type="radio" name="questions[${questionIndex}][correct]" value="0" required>
                        <span class="correct-answer">Correct Answer</span>
                    </div>
                    <div class="answer-block">
                        <input type="text" name="questions[${questionIndex}][answers][1][text]" value="False" readonly required>
                        <input type="radio" name="questions[${questionIndex}][correct]" value="1">
                        <span class="correct-answer">Correct Answer</span>
                    </div>
                `;
                select.closest('.question-block').querySelector('.btn-add').style.display = 'none';
            } else {
                select.closest('.question-block').querySelector('.btn-add').style.display = 'inline-block';
            }
        }
    </script>
</body>
</html> 