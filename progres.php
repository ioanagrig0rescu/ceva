<?php
ob_start();
session_start();

include 'database/db.php';
include 'sesion-check/check.php';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progres - Budget Master</title>
    <?php include 'plugins/bootstrap.html'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            }
        }
    </script>
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f3f3;
            color: #616f80;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
        }

        .content-wrapper {
            flex: 1 0 auto;
            width: 100%;
            margin: 0 !important;
            padding: 120px 0 0 0 !important;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .widget {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .widget-header {
            padding: 1.25rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
        }

        .widget-header h2 {
            color: #2d3748;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .widget-body {
            padding: 1.25rem;
        }

        .progress-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .progress-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .progress-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .progress-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: #718096;
        }

        .progress-percentage {
            font-weight: 600;
            color: #4a5568;
        }

        .chart-container {
            padding: 1rem;
            height: 400px;
        }

        @media (max-width: 768px) {
            .progress-grid {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar/navbar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="main-content">
                <!-- Progress Overview Widget -->
                <div class="widget">
                    <div class="widget-header">
                        <h2>Progres General</h2>
                    </div>
                    <div class="widget-body">
                        <div class="progress-grid">
                            <!-- Progres Obiective -->
                            <div class="progress-card">
                                <div class="progress-title">Obiective Financiare</div>
                                <div class="progress-bar-container">
                                    <?php
                                    // Calculate goals progress
                                    $goals_sql = "SELECT 
                                        COUNT(*) as total_goals,
                                        SUM(CASE WHEN current_amount >= target_amount THEN 1 ELSE 0 END) as completed_goals
                                        FROM goals WHERE user_id = ?";
                                    $stmt = $conn->prepare($goals_sql);
                                    $stmt->bind_param("i", $_SESSION['id']);
                                    $stmt->execute();
                                    $goals_result = $stmt->get_result()->fetch_assoc();
                                    
                                    $goals_progress = $goals_result['total_goals'] > 0 
                                        ? ($goals_result['completed_goals'] / $goals_result['total_goals']) * 100 
                                        : 0;
                                    ?>
                                    <div class="progress-bar" 
                                         style="width: <?php echo $goals_progress; ?>%; 
                                                background: #4299e1;">
                                    </div>
                                </div>
                                <div class="progress-stats">
                                    <span>Obiective Completate</span>
                                    <span class="progress-percentage"><?php echo number_format($goals_progress, 1); ?>%</span>
                                </div>
                            </div>

                            <!-- Progres Venituri -->
                            <div class="progress-card">
                                <div class="progress-title">Venituri Lunare</div>
                                <div class="progress-bar-container">
                                    <?php
                                    // Calculate income progress compared to previous month
                                    $income_sql = "SELECT 
                                        COALESCE(SUM(CASE WHEN MONTH(income_date) = MONTH(CURRENT_DATE) 
                                            AND YEAR(income_date) = YEAR(CURRENT_DATE) 
                                            THEN amount ELSE 0 END), 0) as current_month,
                                        COALESCE(SUM(CASE WHEN MONTH(income_date) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))
                                            AND YEAR(income_date) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))
                                            THEN amount ELSE 0 END), 0) as previous_month
                                        FROM (
                                            SELECT income_date, amount FROM incomes WHERE user_id = ?
                                            UNION ALL
                                            SELECT income_date, amount FROM income_history WHERE user_id = ?
                                        ) as combined_incomes";
                                    $stmt = $conn->prepare($income_sql);
                                    $stmt->bind_param("ii", $_SESSION['id'], $_SESSION['id']);
                                    $stmt->execute();
                                    $income_result = $stmt->get_result()->fetch_assoc();
                                    
                                    $income_progress = $income_result['previous_month'] > 0 
                                        ? ($income_result['current_month'] / $income_result['previous_month']) * 100 
                                        : 0;
                                    if ($income_progress > 100) $income_progress = 100;
                                    ?>
                                    <div class="progress-bar" 
                                         style="width: <?php echo $income_progress; ?>%; 
                                                background: #48bb78;">
                                    </div>
                                </div>
                                <div class="progress-stats">
                                    <span>Față de Luna Precedentă</span>
                                    <span class="progress-percentage"><?php echo number_format($income_progress, 1); ?>%</span>
                                </div>
                            </div>

                            <!-- Progres Economii -->
                            <div class="progress-card">
                                <div class="progress-title">Economii</div>
                                <div class="progress-bar-container">
                                    <?php
                                    // Calculate savings progress (total current amounts vs total target amounts)
                                    $savings_sql = "SELECT 
                                        SUM(current_amount) as total_current,
                                        SUM(target_amount) as total_target
                                        FROM goals WHERE user_id = ?";
                                    $stmt = $conn->prepare($savings_sql);
                                    $stmt->bind_param("i", $_SESSION['id']);
                                    $stmt->execute();
                                    $savings_result = $stmt->get_result()->fetch_assoc();
                                    
                                    $savings_progress = $savings_result['total_target'] > 0 
                                        ? ($savings_result['total_current'] / $savings_result['total_target']) * 100 
                                        : 0;
                                    if ($savings_progress > 100) $savings_progress = 100;
                                    ?>
                                    <div class="progress-bar" 
                                         style="width: <?php echo $savings_progress; ?>%; 
                                                background: #ed64a6;">
                                    </div>
                                </div>
                                <div class="progress-stats">
                                    <span>Din Suma Țintă</span>
                                    <span class="progress-percentage"><?php echo number_format($savings_progress, 1); ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Progress Chart -->
                <div class="widget">
                    <div class="widget-header">
                        <h2>Progres Lunar</h2>
                    </div>
                    <div class="widget-body">
                        <div class="chart-container">
                            <canvas id="monthlyProgressChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer/footer.html'; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Monthly Progress Chart
        const ctx = document.getElementById('monthlyProgressChart').getContext('2d');
        
        // Get last 6 months
        const months = [];
        const currentDate = new Date();
        for (let i = 5; i >= 0; i--) {
            const date = new Date();
            date.setMonth(currentDate.getMonth() - i);
            months.push(date.toLocaleString('ro-RO', { month: 'long' }));
        }
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Venituri',
                    data: [65, 70, 75, 80, 85, 90],
                    borderColor: '#48bb78',
                    tension: 0.4
                }, {
                    label: 'Obiective',
                    data: [40, 45, 50, 55, 60, 65],
                    borderColor: '#4299e1',
                    tension: 0.4
                }, {
                    label: 'Economii',
                    data: [20, 25, 30, 35, 40, 45],
                    borderColor: '#ed64a6',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 