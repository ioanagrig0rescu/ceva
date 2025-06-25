<?php
// Funcție pentru a obține statisticile generale ale obiectivelor
function getGoalsStats($conn, $user_id, $year) {
    $stats = [
        'total' => 0,
        'completed' => 0,
        'inactive' => [],
        'deadline_approaching' => [] // Adăugăm array nou pentru deadline-uri apropiate
    ];
    
    // Obținem toate obiectivele pentru anul specificat
    $sql = "SELECT id, name, current_amount, target_amount, deadline, created_at,
            DATEDIFF(deadline, NOW()) as days_until_deadline,
            DATEDIFF(NOW(), (SELECT MAX(created_at) FROM goal_contributions 
                            WHERE goal_id = goals.id AND type = 'add')) as days_since_last_contribution
            FROM goals 
            WHERE user_id = ? AND YEAR(deadline) = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($goal = $result->fetch_assoc()) {
        $stats['total']++;
        
        // Calculăm progresul în procente pentru toate obiectivele
        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
        
        // Un obiectiv e completat doar dacă a atins 100%
        if ($progress >= 100) {
            $stats['completed']++;
        }
        
        // Verificăm dacă nu s-a contribuit în ultimele 2 săptămâni
        if ($goal['days_since_last_contribution'] > 14 || $goal['days_since_last_contribution'] === null) {
            $stats['inactive'][] = [
                'id' => $goal['id'],
                'name' => $goal['name'],
                'days' => $goal['days_since_last_contribution'] ?? 'Nicio contribuție',
                'type' => 'inactive'
            ];
        }

        // Verificăm TOATE obiectivele necompletate care au deadline în mai puțin de 14 zile
        if ($goal['days_until_deadline'] <= 14 && $goal['days_until_deadline'] > 0 && $progress < 100) {
            $remaining_amount = $goal['target_amount'] - $goal['current_amount'];
            $stats['deadline_approaching'][] = [
                'id' => $goal['id'],
                'name' => $goal['name'],
                'days' => $goal['days_until_deadline'],
                'progress' => round($progress, 1),
                'remaining' => $remaining_amount,
                'type' => 'deadline',
                'deadline' => date('d.m.Y', strtotime($goal['deadline']))
            ];
        }
    }
    
    return $stats;
}

// Obținem anul curent și statisticile
$current_year = date('Y');
$goals_stats = getGoalsStats($conn, $_SESSION['id'], $current_year);

// Combinăm toate alertele într-un singur array
$all_alerts = array_merge($goals_stats['inactive'], $goals_stats['deadline_approaching']);
?>

<style>
.goals-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.goals-summary h4 {
    color: #495057;
    margin-bottom: 15px;
}

.progress-stats {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 15px;
}

.stat-item {
    background: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-align: center;
    flex: 1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #0056b3;
    margin-bottom: 5px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.alerts-section {
    margin-top: 20px;
}

.alert-item {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.alert-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alert-item.deadline-alert {
    background-color: #f8d7da;
    border-left-color: #dc3545;
}

.alert-item.deadline-alert i {
    color: #dc3545;
}

.alert-item.deadline-alert .alert-text {
    color: #721c24;
}

.alert-item i {
    color: #ffc107;
    font-size: 1.2rem;
}

.alert-item .alert-text {
    flex: 1;
    color: #856404;
}

.alert-item .alert-link {
    color: inherit;
    text-decoration: none;
    font-weight: 500;
}

.alert-item .alert-link:hover {
    text-decoration: underline;
}

.highlight-goal {
    animation: highlight-pulse 2s ease-in-out;
}

@keyframes highlight-pulse {
    0% { background-color: rgba(255, 193, 7, 0.2); }
    50% { background-color: rgba(255, 193, 7, 0.4); }
    100% { background-color: transparent; }
}
</style>

<div class="goals-summary">
    <h4><i class="fas fa-chart-line me-2"></i>Progres General</h4>
    
    <div class="progress-stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo $goals_stats['completed']; ?>/<?php echo $goals_stats['total']; ?></div>
            <div class="stat-label">Obiective Atinse în <?php echo $current_year; ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo count($all_alerts); ?></div>
            <div class="stat-label">Necesită Atenție</div>
        </div>
    </div>

    <?php if (!empty($all_alerts)): ?>
    <div class="alerts-section">
        <h5><i class="fas fa-bell me-2"></i>Alerte și Sugestii</h5>
        <?php foreach ($all_alerts as $alert): ?>
            <div class="alert-item <?php echo $alert['type'] === 'deadline' ? 'deadline-alert' : ''; ?>" 
                 onclick="scrollToGoal(<?php echo $alert['id']; ?>)">
                <i class="fas <?php echo $alert['type'] === 'deadline' ? 'fa-clock' : 'fa-exclamation-triangle'; ?>"></i>
                <div class="alert-text">
                    <?php if ($alert['type'] === 'deadline'): ?>
                        <strong>Atenție!</strong> Mai ai doar <?php echo $alert['days']; ?> zile până la termenul limită 
                        (<?php echo $alert['deadline']; ?>) pentru obiectivul
                        "<strong><?php echo htmlspecialchars($alert['name']); ?></strong>"!
                        <br>
                        <small class="text-muted">
                            Progres actual: <?php echo $alert['progress']; ?>% - 
                            Mai ai de strâns <?php echo number_format($alert['remaining'], 2, ',', '.'); ?> RON
                        </small>
                    <?php else: ?>
                        Nu ai contribuit la obiectivul 
                        "<strong><?php echo htmlspecialchars($alert['name']); ?></strong>" 
                        <?php 
                        if (is_numeric($alert['days'])) {
                            echo 'de ' . $alert['days'] . ' zile';
                        } else {
                            echo 'încă';
                        }
                        ?>!
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function scrollToGoal(goalId) {
    const goalElement = document.getElementById('goal-' + goalId);
    if (goalElement) {
        // Calculăm poziția elementului relativ la viewport
        const rect = goalElement.getBoundingClientRect();
        const absoluteTop = rect.top + window.pageYOffset;
        
        // Scrollăm cu un offset pentru a lua în considerare header-ul fix
        window.scrollTo({
            top: absoluteTop - 100, // Ajustează această valoare în funcție de înălțimea header-ului tău
            behavior: 'smooth'
        });
        
        // Adăugăm highlight
        goalElement.classList.add('highlight-goal');
        setTimeout(() => {
            goalElement.classList.remove('highlight-goal');
        }, 2000);
    }
}
</script> 