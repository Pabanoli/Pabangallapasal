<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'rosan_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'डाटाबेस कनेक्सन त्रुटि भयो। कृपया पुनः प्रयास गर्नुहोस्।'
    ]);
    exit();
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $errors = [];

    if (empty($email)) {
        $errors[] = "इमेल आवश्यक छ";
    }

    if (empty($password)) {
        $errors[] = "पासवर्ड आवश्यक छ";
    }

    if (empty($errors)) {
        try {
            // Check if users table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() == 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'डाटाबेस तालिका भेटिएन। कृपया प्रशासकलाई सम्पर्क गर्नुहोस्।'
                ]);
                exit();
            }

            // Get user from database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'लग इन सफल भयो!',
                    'user' => [
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'profile_picture' => $user['profile_picture'] ?? null
                    ]
                ]);
                exit();
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'अमान्य इमेल वा पासवर्ड'
                ]);
                exit();
            }
        } catch(PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'लग इन प्रक्रियामा त्रुटि भयो। कृपया पुनः प्रयास गर्नुहोस्।'
            ]);
            exit();
        }
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        exit();
    }
}
?> 