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
   <link rel="stylesheet" href="style/dashboard.css">
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
                        <a href="login.php" class="nav-link">Logout</a>
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
                    <select class="filter-dropdown" onchange="window.location.href='dashboard.php?filter=' + this.value">
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