<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['staff'])) {
    if ($_SESSION['staff']['role'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: kitchen.php');
    }
    exit;
}

$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        $query = "SELECT * FROM staff WHERE username = :username AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $staff['password'])) {
                $_SESSION['staff'] = $staff;
                
                // Redirect based on role
                if ($staff['role'] === 'admin') {
                    header('Location: dashboard.php');
                } else {
                    header('Location: kitchen.php');
                }
                exit;
            }
        }
        
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2 class="login-title">Staff Login</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Login</button>
            </form>
            <p style="text-align: center; margin-top: 1rem; color: var(--secondary); font-size: 0.9rem;">
                Demo Accounts:<br>
                Admin: username: <strong>admin</strong>, password: <strong>password</strong><br>
                Kitchen: username: <strong>kitchen</strong>, password: <strong>password</strong>
            </p>
        </div>
    </div>
</body>
</html>