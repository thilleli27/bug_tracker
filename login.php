<?php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'bugtracker');
define('DB_USER', 'root');
define('DB_PASS', '');

// Messages d'erreur
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Bug Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #333333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            text-align: center;
        }
        
        .header {
            margin-bottom: 50px;
        }
        
        h1 {
            color: white;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .subtitle {
            color: #a0aec0;
            font-size: 1.5rem;
            font-weight: 300;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 50px 60px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 0 60px rgba(72, 187, 172, 0.3);
        }
        
        .login-card h2 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #718096;
            margin-bottom: 40px;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        label {
            display: block;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f7fafc;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #48bbac;
            background: white;
        }
        
        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
            border-left: 4px solid #c53030;
        }
        
        .submit-btn {
            width: 100%;
            max-width: 300px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #48bbac 0%, #3da89a 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(72, 187, 172, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .links {
            margin-top: 25px;
            color: #718096;
        }
        
        .links a {
            color: #48bbac;
            text-decoration: none;
            font-weight: 600;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1.2rem;
            }
            
            .login-card {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Track Bugs Efficiently</h1>
            <p class="subtitle">Manage Issues with Confidence</p>
        </div>
        
        <div class="login-card">
            <h2>Welcome back</h2>
            <p class="login-subtitle">Sign in to your account</p>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                    >
                </div>
                
                <button type="submit" class="submit-btn">Sign in</button>
            </form>
            
            <div class="links">
                <p>Don't have an account </p> | 
                <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</body>
</html>