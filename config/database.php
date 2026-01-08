<?php


// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'bugtracker');
define('DB_USER', 'root');
define('DB_PASS', '');

// Fonction pour obtenir une connexion PDO
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger vers la page de connexion
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Fonction pour obtenir l'utilisateur actuel
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name']
    ];
}

// Fonction pour se déconnecter
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fonction pour obtenir les statistiques des tickets
function getTicketStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_tickets,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tickets,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_tickets,
            SUM(CASE WHEN priority = 'Critical' THEN 1 ELSE 0 END) as critical_tickets
        FROM tickets
    ");
    return $stmt->fetch();
}

// Fonction pour obtenir tous les tickets avec détails
function getAllTickets($pdo, $limit = null) {
    $sql = "
        SELECT 
            t.*, 
            u.name as creator_name, 
            a.name as assigned_name, 
            c.name as category_name
        FROM tickets t
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users a ON t.assigned_to = a.id
        JOIN categories c ON t.category_id = c.id
        ORDER BY t.created_at DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Fonction pour obtenir un ticket par ID
function getTicketById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT 
            t.*, 
            u.name as creator_name, 
            a.name as assigned_name, 
            c.name as category_name
        FROM tickets t
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users a ON t.assigned_to = a.id
        JOIN categories c ON t.category_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Fonction pour obtenir toutes les catégories
function getAllCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// Fonction pour obtenir tous les utilisateurs
function getAllUsers($pdo) {
    $stmt = $pdo->query("SELECT id, name, email FROM users ORDER BY name");
    return $stmt->fetchAll();
}
?>