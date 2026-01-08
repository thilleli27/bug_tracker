<?php
session_start();
require_once 'config/database.php';



$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($firstName)) {
        $errors[] = "Le prénom est requis";
    }
    
    if (empty($lastName)) {
        $errors[] = "Le nom est requis";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    // Si pas d'erreurs, vérifier si l'email existe déjà
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé";
            } else {
                // Créer le compte
                $fullName = $firstName . ' ' . $lastName;
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$fullName, $email, $hashedPassword]);
                
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la création du compte : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - BugTracker</title>
    <link rel="stylesheet" href="style/register.css">
</head>
<body>
    <div class="container">
        <h1>Join BugTracker</h1>
        <p class="subtitle">Start tracking bugs efficiently today</p>
        
        <div class="form-card">
            <h2 class="form-title">Create your account</h2>
            <p class="form-subtitle">Fill in the details to get started</p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Compte créé avec succès ! <a href="login.php">Se connecter maintenant</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                        >
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="confirm_password">Confirm Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
            <?php endif; ?>
        </div>
        
        <p class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </p>
    </div>
</body>
</html>
