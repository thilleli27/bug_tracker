<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Récupérer le filtre
$filter = $_GET['filter'] ?? 'all';

// Récupérer les statistiques
$stats = getTicketStats($pdo);

// Construire la requête en fonction du filtre
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
";

$params = [];

if ($filter === 'my_tickets') {
    $sql .= " WHERE t.created_by = ? OR t.assigned_to = ?";
    $params = [$currentUser['id'], $currentUser['id']];
} elseif ($filter === 'front-end') {
    $sql .= " WHERE c.name = 'Front-end'";
} elseif ($filter === 'back-end') {
    $sql .= " WHERE c.name = 'Back-end'";
} elseif ($filter === 'infrastructure') {
    $sql .= " WHERE c.name = 'Infrastructure'";
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Gérer la déconnexion
if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BugTracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #0d3d3d 0%, #0a1f1f 100%);
            min-height: 100vh;
            color: white;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(13, 61, 61, 0.8);
            border-right: 3px solid rgba(72, 187, 170, 0.3);
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
            background: #48bbaa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
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
            color: #8bb4b1;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(72, 187, 170, 0.2);
            color: #48bbaa;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 3rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header-title h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .header-subtitle {
            color: #48bbaa;
            font-size: 1.25rem;
        }
        
        .btn-new-bug {
            padding: 0.75rem 2rem;
            background: #48bbaa;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-new-bug:hover {
            background: #3aa896;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(72, 187, 170, 0.3);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: rgba(13, 61, 61, 0.6);
            border: 2px solid rgba(72, 187, 170, 0.3);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(72, 187, 170, 0.2);
        }
        
        .stat-label {
            color: #48bbaa;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            color: white;
        }
        
        /* Recent Bugs Section */
        .recent-bugs {
            background: rgba(13, 61, 61, 0.6);
            border: 2px solid rgba(72, 187, 170, 0.3);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(72, 187, 170, 0.2);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .filter-dropdown {
            padding: 0.5rem 1rem;
            background: rgba(72, 187, 170, 0.2);
            border: 1px solid rgba(72, 187, 170, 0.3);
            border-radius: 8px;
            color: #48bbaa;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            border-bottom: 2px solid rgba(72, 187, 170, 0.3);
        }
        
        th {
            padding: 0.75rem 0.5rem;
            text-align: left;
            color: #48bbaa;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 1rem 0.5rem;
            border-bottom: 1px solid rgba(72, 187, 170, 0.1);
            color: #cbd5e0;
            font-size: 0.9rem;
        }
        
        tr:hover {
            background: rgba(72, 187, 170, 0.05);
        }
        
        .bug-id {
            color: #48bbaa;
            font-weight: 600;
        }
        
        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .badge-open {
            background: rgba(237, 137, 54, 0.2);
            color: #ed8936;
        }
        
        .badge-progress {
            background: rgba(66, 153, 225, 0.2);
            color: #4299e1;
        }
        
        .badge-closed {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
        }
        
        .badge-high {
            background: rgba(245, 101, 101, 0.2);
            color: #f56565;
        }
        
        .badge-medium {
            background: rgba(237, 137, 54, 0.2);
            color: #ed8936;
        }
        
        .badge-low {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
        }
        
        .badge-critical {
            background: rgba(197, 48, 48, 0.3);
            color: #fc8181;
        }
        
        .status-select {
            padding: 0.3rem 0.5rem;
            background: rgba(72, 187, 170, 0.1);
            border: 1px solid rgba(72, 187, 170, 0.3);
            border-radius: 6px;
            color: #48bbaa;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            max-width: 120px;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            transition: transform 0.2s;
            padding: 0.2rem;
        }
        
        .action-btn:hover {
            transform: scale(1.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #8bb4b1;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .empty-state-text {
            font-size: 1.1rem;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-dashboard: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: rgba(13, 61, 61, 0.95);
            border: 2px solid rgba(72, 187, 170, 0.3);
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .modal-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .modal-text {
            color: #cbd5e0;
            margin-bottom: 2rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background: #f56565;
            color: white;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
        }
        
        .btn-cancel {
            background: rgba(203, 213, 224, 0.2);
            color: #cbd5e0;
        }
        
        .btn-cancel:hover {
            background: rgba(203, 213, 224, 0.3);
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            z-dashboard: 2000;
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            background: #48bb78;
        }

        .notification.error {
            background: #f56565;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 3px solid rgba(72, 187, 170, 0.3);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Notification -->
    <div id="notification" class="notification"></div>

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
                        <a href="dashboard.php" class="nav-link active">Dashboard</a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="?logout=1" class="nav-link">Logout</a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1>Dashboard</h1>
                    <p class="header-subtitle">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?></p>
                </div>
                <a href="new_ticket.php" class="btn-new-bug">+ New Bug</a>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Bugs</div>
                    <div class="stat-value" id="stat-total"><?php echo $stats['total_tickets']; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Open</div>
                    <div class="stat-value" id="stat-open"><?php echo $stats['open_tickets']; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">In Progress</div>
                    <div class="stat-value" id="stat-progress"><?php echo $stats['in_progress_tickets']; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Resolved</div>
                    <div class="stat-value" id="stat-closed"><?php echo $stats['closed_tickets']; ?></div>
                </div>
            </div>
            
            <!-- Recent Bugs Table -->
            <div class="recent-bugs">
                <div class="section-header">
                    <h2 class="section-title">Recent Bugs</h2>
                    <select class="filter-dropdown" onchange="window.location.href='?filter=' + this.value">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Filter: All tickets ↓</option>
                        <option value="my_tickets" <?php echo $filter === 'my_tickets' ? 'selected' : ''; ?>>My Tickets</option>
                        <option value="front-end" <?php echo $filter === 'front-end' ? 'selected' : ''; ?>>Category: Front-end</option>
                        <option value="back-end" <?php echo $filter === 'back-end' ? 'selected' : ''; ?>>Category: Back-end</option>
                        <option value="infrastructure" <?php echo $filter === 'infrastructure' ? 'selected' : ''; ?>>Category: Infrastructure</option>
                    </select>
                </div>
                
                <?php if (count($tickets) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>TITLE</th>
                                <th>CATEGORY</th>
                                <th>CREATED DATE</th>
                                <th>CREATOR</th>
                                <th>STATUS</th>
                                <th>PRIORITY</th>
                                <th>ASSIGNED TO</th>
                                <th>RESOLVED AT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="tickets-tbody">
                            <?php foreach ($tickets as $ticket): ?>
                            <tr data-ticket-id="<?php echo $ticket['id']; ?>">
                                <td class="bug-id">#<?php echo $ticket['id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['category_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($ticket['creator_name']); ?></td>
                                <td>
                                    <select class="status-select" onchange="updateStatus(<?php echo $ticket['id']; ?>, this.value)">
                                        <option value="Open" <?php echo $ticket['status'] === 'Open' ? 'selected' : ''; ?>>OPEN</option>
                                        <option value="In Progress" <?php echo $ticket['status'] === 'In Progress' ? 'selected' : ''; ?>>IN PROGRESS</option>
                                        <option value="Closed" <?php echo $ticket['status'] === 'Closed' ? 'selected' : ''; ?>>CLOSED</option>
                                    </select>
                                </td>
                                <td>
                                    <?php 
                                    $priorityClass = '';
                                    if ($ticket['priority'] === 'Critical') $priorityClass = 'badge-critical';
                                    elseif ($ticket['priority'] === 'High') $priorityClass = 'badge-high';
                                    elseif ($ticket['priority'] === 'Medium') $priorityClass = 'badge-medium';
                                    else $priorityClass = 'badge-low';
                                    ?>
                                    <span class="badge <?php echo $priorityClass; ?>">
                                        <?php echo strtoupper($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo $ticket['assigned_name'] ? htmlspecialchars($ticket['assigned_name']) : '-'; ?></td>
                                <td class="resolved-at"><?php echo $ticket['resolved_at'] ? date('d/m/Y H:i', strtotime($ticket['resolved_at'])) : '-'; ?></td>
                                <td class="actions">
                                    <button class="action-btn" title="Edit" onclick="window.location.href='new_ticket.php?id=<?php echo $ticket['id']; ?>'">🖊️</button>
                                    <button class="action-btn" title="Delete" onclick="confirmDelete(<?php echo $ticket['id']; ?>)">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🐛</div>
                    <div class="empty-state-title">No tickets found</div>
                    <div class="empty-state-text">
                        <?php if ($filter === 'my_tickets'): ?>
                            You haven't created or been assigned any tickets yet.
                        <?php else: ?>
                            No tickets match this filter. Create a new one!
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">⚠️ Confirm Deletion</h2>
            <p class="modal-text">Are you sure you want to delete this ticket? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-danger" onclick="deleteTicket()">Delete</button>
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>
    
    <script>
        let ticketToDelete = null;
        
        // Fonction pour afficher une notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
        
        // Fonction pour mettre à jour les statistiques
        function updateStats() {
            fetch('actions.php?get_stats=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('stat-total').textContent = data.stats.total_tickets;
                        document.getElementById('stat-open').textContent = data.stats.open_tickets;
                        document.getElementById('stat-progress').textContent = data.stats.in_progress_tickets;
                        document.getElementById('stat-closed').textContent = data.stats.closed_tickets;
                    }
                })
                .catch(error => console.error('Erreur lors de la mise à jour des stats:', error));
        }
        
        // Fonction pour mettre à jour le statut
        function updateStatus(ticketId, newStatus) {
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('ticket_id', ticketId);
            formData.append('status', newStatus);
            
            fetch('actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Status updated successfully!', 'success');
                    
                    // Mettre à jour resolved_at dans l'interface
                    const row = document.querySelector(`tr[data-ticket-id="${ticketId}"]`);
                    const resolvedAtCell = row.querySelector('.resolved-at');
                    
                    if (newStatus === 'Closed') {
                        const now = new Date();
                        const formatted = now.toLocaleDateString('fr-FR') + ' ' + 
                                        now.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
                        resolvedAtCell.textContent = formatted;
                    } else {
                        resolvedAtCell.textContent = '-';
                    }
                    
                    // Mettre à jour les statistiques
                    updateStats();
                } else {
                    showNotification('Error updating status: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Connection error', 'error');
            });
        }
        
        // Fonction pour confirmer la suppression
        function confirmDelete(ticketId) {
            ticketToDelete = ticketId;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        // Fonction pour fermer le modal
        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
            ticketToDelete = null;
        }
        
        // Fonction pour supprimer le ticket
        function deleteTicket() {
            if (ticketToDelete) {
                const formData = new FormData();
                formData.append('delete_ticket', '1');
                formData.append('ticket_id', ticketToDelete);
                
                fetch('actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Ticket deleted successfully!', 'success');
                        
                        // Supprimer la ligne du tableau
                        const row = document.querySelector(`tr[data-ticket-id="${ticketToDelete}"]`);
                        if (row) {
                            row.remove();
                        }
                        
                        // Mettre à jour les statistiques
                        updateStats();
                        
                        closeModal();
                        
                        // Vérifier s'il reste des tickets
                        const tbody = document.getElementById('tickets-tbody');
                        if (tbody && tbody.children.length === 0) {
                            location.reload();
                        }
                    } else {
                        showNotification('Error deleting ticket: ' + (data.error || 'Unknown error'), 'error');
                        closeModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Connection error', 'error');
                    closeModal();
                });
            }
        }
        
        // Fermer le modal en cliquant à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>