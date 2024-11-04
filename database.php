<?php
session_start();
$errorMessage = "";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_data";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $name = htmlspecialchars(trim($_POST['name']));
        $age = intval($_POST['age']);
        $username = htmlspecialchars(trim($_POST['username']));
        $password = htmlspecialchars(trim($_POST['password']));

        if (empty($name) || $age <= 0 || empty($username) || empty($password)) {
            $errorMessage = "All fields are required.";
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit();
        } else {
            $_SESSION['name'] = $name;
            $_SESSION['age'] = $age;
            $_SESSION['username'] = $username;


            $stmt = $conn->prepare("INSERT INTO users (name, age, username, password) VALUES (?, ?, ?, ?)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("siss", $name, $age, $username, $hashedPassword);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'redirect' => 'intprog.php']);
            } else {
                echo json_encode(['success' => false, 'message' => "Error: " . $stmt->error]);
            }

            $stmt->close();
            exit();
        }
    } else {

        $username = htmlspecialchars(trim($_POST['username']));
        $password = htmlspecialchars(trim($_POST['password']));

        if (empty($username) || empty($password)) {
            $errorMessage = "All fields are required.";
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit();
        }

        $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['username'] = $username;
                echo json_encode(['success' => true, 'redirect' => 'intprog.php']);
            } else {
                echo json_encode(['success' => false, 'message' => "Invalid username or password."]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Invalid username or password."]);
        }

        $stmt->close();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Registration</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #3AAFA9 url('bgtech1.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            margin: 0;
            padding: 50px;
            text-align: center;
        }

        h1 {
            font-size: 3em;
            color: #fff;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        h2 {
            font-size: 1.5em;
            color: #fff;
            margin-bottom: 30px;
        }

        .form-container {
            margin-top: 20px;
            display: inline-block;
            text-align: left;
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            width: 320px;
        }

        .form-container label {
            font-size: 1.2em;
            display: block;
            margin-bottom: 10px;
            color: #333;
        }

        .form-container input {
            font-size: 1em;
            padding: 10px;
            width: calc(100% - 22px);
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-container input:focus {
            outline: none;
            border-color: #004d40;
            box-shadow: 0 0 5px #004d40;
        }

        .form-container button {
            font-size: 1.2em;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            background-color: #004d40;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-bottom: 15px;
        }

        #showRegister, #showLogin {
            background-color: #fff;
            color: #004d40;
            border: 2px solid #004d40;
            transition: background-color 0.3s ease;
        }

        #showRegister:hover, #showLogin:hover {
            background-color: #004d40;
            color: #fff; 
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Welcome, My Friend</h1>
    <h2>Please fill out all required fields to log in or register!</h2>

    <div class="form-container" id="loginFormContainer">
        <h3>Log In</h3>
        <form id="loginForm" method="post">
            <label for="loginUsername">Username:</label>
            <input type="text" id="loginUsername" name="username" required>
            <label for="loginPassword">Password:</label>
            <input type="password" id="loginPassword" name="password" required>
            <button type="submit">Log In</button>
        </form>
        <div id="loginMessage"><?php if (isset($errorMessage)) echo $errorMessage; ?></div>
        <button id="showRegister">Don't have an account? Register here</button>
    </div>

    <div class="form-container" id="registerFormContainer" style="display: none;">
        <h3>Register</h3>
        <form id="registerForm" method="post">
            <input type="hidden" name="action" value="register">
            <label for="registerName">Name:</label>
            <input type="text" id="registerName" name="name" required>
            <label for="registerAge">Age:</label>
            <input type="number" id="registerAge" name="age" min="1" required>
            <label for="registerUsername">Username:</label>
            <input type="text" id="registerUsername" name="username" required>
            <label for="registerPassword">Password:</label>
            <input type="password" id="registerPassword" name="password" required>
            <button type="submit">Register</button>
        </form>
        <div id="registerMessage"></div>
        <button id="showLogin">Already have an account? Log In</button>
    </div>

    <script>
    $(document).ready(function() {
        $('#showRegister').on('click', function() {
            $('#loginFormContainer').hide();
            $('#registerFormContainer').show();
        });

        $('#showLogin').on('click', function() {
            $('#registerFormContainer').hide();
            $('#loginFormContainer').show();
        });

        $('#loginForm').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: '',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    } else {
                        $('#loginMessage').text(response.message);
                    }
                },
                error: function() {
                    $('#loginMessage').text('An error occurred. Please try again.');
                }
            });
        });

        $('#registerForm').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: '',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    } else {
                        $('#registerMessage').text(response.message);
                    }
                },
                error: function() {
                    $('#registerMessage').text('An error occurred. Please try again.');
                }
            });
        });
    });
    </script>
</body>
</html>
