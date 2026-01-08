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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #333333;
            min-height: 100vh;
            color: #FCFAF9;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #2a2a2a;
            border-right: 3px solid rgba(72, 229, 194, 0.3);
            padding: 2rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: #48E5C2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #333333;
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: #FCFAF9;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: block;
            padding: 1rem 1.5rem;
            color: #a0a0a0;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(72, 229, 194, 0.1);
            color: #48E5C2;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 3rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: #FCFAF9;
        }
        
        .page-subtitle {
            color: #a0a0a0;
            font-size: 1.1rem;
        }
        
        /* Form Card */
        .form-card {
            background: #2a2a2a;
            border: 2px solid rgba(72, 229, 194, 0.2);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(72, 229, 194, 0.15);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .alert-error {
            background: rgba(245, 101, 101, 0.15);
            border: 1px solid #f56565;
            color: #fc8181;
        }
        
        .alert ul {
            list-style: none;
            padding-left: 0;
        }
        
        .alert li {
            margin: 0.25rem 0;
        }
        
        .form-grid {
            display: grid;
            gap: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            color: #48E5C2;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input, select, textarea {
            padding: 1rem;
            background: #333333;
            border: 2px solid rgba(72, 229, 194, 0.2);
            border-radius: 10px;
            color: #FCFAF9;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #48E5C2;
            background: #3a3a3a;
            box-shadow: 0 0 0 3px rgba(72, 229, 194, 0.1);
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
        }
        
        select {
            cursor: pointer;
        }
        
        select option {
            background: #333333;
            color: #FCFAF9;
        }
        
        input::placeholder, textarea::placeholder {
            color: #666;
        }
        
        /* Buttons */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1.25rem 3rem;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #48E5C2;
            color: #333333;
            flex: 1;
            box-shadow: 0 4px 15px rgba(72, 229, 194, 0.3);
        }
        
        .btn-primary:hover {
            background: #3dd4b3;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72, 229, 194, 0.4);
        }
        
        .btn-secondary {
            background: transparent;
            color: #a0a0a0;
            border: 2px solid rgba(160, 160, 160, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(160, 160, 160, 0.1);
            color: #FCFAF9;
            border-color: rgba(160, 160, 160, 0.5);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 3px solid rgba(72, 229, 194, 0.3);
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .form-card {
                padding: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
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