<?php
session_start();
require_once 'config/database.php';

// Activer les erreurs pour le débogage (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs dans la réponse
ini_set('log_errors', 1);

// Définir le header JSON dès le début
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$pdo = getDBConnection();

// Récupérer les statistiques (pour AJAX)
if (isset($_GET['get_stats'])) {
    try {
        $stats = getTicketStats($pdo);
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        error_log("Erreur get_stats: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Gérer le changement de statut
if (isset($_POST['update_status'])) {
    try {
        $ticketId = intval($_POST['ticket_id']);
        $newStatus = $_POST['status'];
        
        // Log pour débogage
        error_log("Mise à jour du ticket #$ticketId vers le statut: $newStatus");
        
        // Vérifier que le statut est valide
        $validStatuses = ['Open', 'In Progress', 'Closed'];
        if (!in_array($newStatus, $validStatuses)) {
            error_log("Statut invalide: $newStatus");
            echo json_encode(['success' => false, 'error' => 'Statut invalide']);
            exit;
        }
        
        // Préparer la requête en fonction du statut
        if ($newStatus === 'Closed') {
            // Si fermé, mettre à jour resolved_at
            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, resolved_at = NOW(), updated_at = NOW() WHERE id = ?");
        } else {
            // Sinon, réinitialiser resolved_at
            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, resolved_at = NULL, updated_at = NOW() WHERE id = ?");
        }
        
        $result = $stmt->execute([$newStatus, $ticketId]);
        
        error_log("Résultat UPDATE: " . ($result ? 'success' : 'failed'));
        error_log("Lignes affectées: " . $stmt->rowCount());
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour']);
        }
    } catch (Exception $e) {
        error_log("Erreur update_status: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Gérer la suppression
if (isset($_POST['delete_ticket'])) {
    try {
        $ticketId = intval($_POST['ticket_id']);
        
        error_log("Suppression du ticket #$ticketId");
        
        // Vérifier que le ticket existe
        $checkStmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ?");
        $checkStmt->execute([$ticketId]);
        
        if (!$checkStmt->fetch()) {
            error_log("Ticket #$ticketId introuvable");
            echo json_encode(['success' => false, 'error' => 'Ticket introuvable']);
            exit;
        }
        
        // Supprimer le ticket
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
        $result = $stmt->execute([$ticketId]);
        
        error_log("Résultat DELETE: " . ($result ? 'success' : 'failed'));
        error_log("Lignes supprimées: " . $stmt->rowCount());
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Ticket supprimé avec succès']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Échec de la suppression']);
        }
    } catch (Exception $e) {
        error_log("Erreur delete_ticket: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Si aucune action reconnue
echo json_encode(['success' => false, 'error' => 'Action invalide']);
?>