<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Récupérer les catégories et utilisateurs pour les selects
$categories = getAllCategories($pdo);
$users = getAllUsers($pdo);

$errors = [];
$success = false;

// Vérifier si on est en mode édition
$isEdit = false;
$ticket = null;
if (isset($_GET['id'])) {
    $isEdit = true;
    $ticket = getTicketById($pdo, $_GET['id']);
    if (!$ticket) {
        header('Location: dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $status = $_POST['status'] ?? 'Open';
    $assigned_to = $_POST['assigned_to'] ?? null;
    
    // Validation
    if (empty($title)) {
        $errors[] = "Le titre est requis";
    }
    
    if (empty($category_id)) {
        $errors[] = "La catégorie est requise";
    }
    
    if (empty($priority)) {
        $errors[] = "La priorité est requise";
    }
    
    // Si pas d'erreurs, sauvegarder
    if (empty($errors)) {
        try {
            if ($isEdit) {
                // Mise à jour
                $stmt = $pdo->prepare("
                    UPDATE tickets 
                    SET title = ?, description = ?, category_id = ?, priority = ?, status = ?, assigned_to = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $category_id, $priority, $status, $assigned_to ?: null, $_GET['id']]);
            } else {
                // Création
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (title, description, category_id, priority, status, created_by, assigned_to)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $category_id, $priority, $status, $currentUser['id'], $assigned_to ?: null]);
            }
            
            header('Location: dashboard.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la sauvegarde : " . $e->getMessage();
        }
    }
}

// Gérer la déconnexion
if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Modifier' : 'Nouveau'; ?> Ticket - BugTracker</title>
    <link rel="stylesheet" href="style/new_ticket.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <div class="logo-icon">✓</div>
                <span class="logo-text">BugTracker</span>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">Dashboard</a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="new_ticket.php" class="nav-link active">New Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a href="?logout=1" class="nav-link">Logout</a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <?php echo $isEdit ? '✏️ Modifier le Ticket' : '➕ Nouveau Ticket'; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo $isEdit ? 'Modifiez les informations du ticket' : 'Remplissez les informations pour créer un nouveau ticket'; ?>
                </p>
            </div>
            
            <div class="form-card">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li>⚠️ <?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <!-- Titre -->
                        <div class="form-group full-width">
                            <label for="title">Titre du Ticket *</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                placeholder="Ex: Bouton de connexion ne répond pas"
                                value="<?php echo htmlspecialchars($isEdit ? $ticket['title'] : ($_POST['title'] ?? '')); ?>"
                                required
                            >
                        </div>
                        
                        <!-- Description -->
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                placeholder="Décrivez le bug en détail..."
                            ><?php echo htmlspecialchars($isEdit ? $ticket['description'] : ($_POST['description'] ?? '')); ?></textarea>
                        </div>
                        
                        <!-- Row 1: Catégorie et Priorité -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id">Catégorie *</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"
                                            <?php echo ($isEdit && $ticket['category_id'] == $cat['id']) || (!$isEdit && ($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">Priorité *</label>
                                <select id="priority" name="priority" required>
                                    <option value="">Sélectionner une priorité</option>
                                    <?php 
                                    $priorities = ['Low', 'Medium', 'High', 'Critical'];
                                    $priorityLabels = ['Low' => 'Basse', 'Medium' => 'Moyenne', 'High' => 'Haute', 'Critical' => 'Critique'];
                                    foreach ($priorities as $p): 
                                    ?>
                                        <option value="<?php echo $p; ?>"
                                            <?php echo ($isEdit && $ticket['priority'] == $p) || (!$isEdit && ($_POST['priority'] ?? '') == $p) ? 'selected' : ''; ?>>
                                            <?php echo $priorityLabels[$p]; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Row 2: Statut et Assigné à -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Statut</label>
                                <select id="status" name="status">
                                    <?php 
                                    $statuses = ['Open' => 'Ouvert', 'In Progress' => 'En cours', 'Closed' => 'Fermé'];
                                    foreach ($statuses as $value => $label): 
                                    ?>
                                        <option value="<?php echo $value; ?>"
                                            <?php echo ($isEdit && $ticket['status'] == $value) || (!$isEdit && ($_POST['status'] ?? 'Open') == $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="assigned_to">Assigné à</label>
                                <select id="assigned_to" name="assigned_to">
                                    <option value="">Non assigné</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($isEdit && $ticket['assigned_to'] == $user['id']) || (!$isEdit && ($_POST['assigned_to'] ?? '') == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            💾 <?php echo $isEdit ? 'Mettre à jour' : 'Sauvegarder'; ?>
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            ❌ Annuler
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>