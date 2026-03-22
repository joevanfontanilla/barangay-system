<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_config.php'; 

// 1. Total Population
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM residents");
$totalResidents = $stmtTotal->fetchColumn();

// 2. Gender Data
$stmtGender = $pdo->query("SELECT gender, COUNT(*) as count FROM residents GROUP BY gender");
$genderData = $stmtGender->fetchAll();

// 3. Civil Status Data (NEW)
$stmtStatus = $pdo->query("SELECT civil_status, COUNT(*) as count FROM residents GROUP BY civil_status");
$statusData = $stmtStatus->fetchAll();

// 4. Purok Population (Numerical Sort)
$stmtPurok = $pdo->query("SELECT purok, COUNT(*) as count 
                          FROM residents 
                          GROUP BY purok 
                          ORDER BY CAST(REPLACE(REPLACE(LOWER(purok), 'purok', ''), ' ', '') AS UNSIGNED) ASC");
$purokData = $stmtPurok->fetchAll();

// 5. Age Demographics
$stmtAge = $pdo->query("SELECT 
    SUM(CASE WHEN DATEDIFF(CURRENT_DATE, birthdate)/365 < 18 THEN 1 ELSE 0 END) AS youth,
    SUM(CASE WHEN DATEDIFF(CURRENT_DATE, birthdate)/365 BETWEEN 18 AND 59 THEN 1 ELSE 0 END) AS adults,
    SUM(CASE WHEN DATEDIFF(CURRENT_DATE, birthdate)/365 >= 60 THEN 1 ELSE 0 END) AS seniors
    FROM residents");
$ageData = $stmtAge->fetch();

// 6. Dynamic Voter Status Data
$stmtVoter = $pdo->query("SELECT voter_status, COUNT(*) as count FROM residents GROUP BY voter_status");
$voterData = $stmtVoter->fetchAll();

// Optional: Calculate "Potential Voters" (Those 18+ who aren't registered yet)
$stmtPotential = $pdo->query("SELECT COUNT(*) FROM residents WHERE DATEDIFF(CURRENT_DATE, birthdate)/365 >= 18 AND voter_status = 'Non-Registered'");
$potentialVoters = $stmtPotential->fetchColumn();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .analytics-container { padding: 15px; font-family: 'Segoe UI', sans-serif; max-width: 1000px; margin: auto; color: #1c1e21; }
    
    /* Stats Row */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
    .stat-card { background: white; padding: 12px; border-radius: 8px; border-top: 3px solid #1877f2; box-shadow: 0 2px 4px rgba(0,0,0,0.05); text-align: center; }
    .stat-card h5 { margin: 0; color: #65676b; font-size: 0.7rem; text-transform: uppercase; }
    .stat-card h2 { margin: 5px 0 0; font-size: 1.3rem; }

    /* Grid & Charts - Updated to 2x2 for the top section */
    .analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
    .chart-card { background: white; padding: 15px; border-radius: 10px; border: 1px solid #eee; display: flex; flex-direction: column; align-items: center; }
    .chart-card h4 { margin: 0 0 10px 0; font-size: 0.8rem; color: #1877f2; text-transform: uppercase; align-self: flex-start; border-bottom: 1px solid #f0f2f5; width: 100%; padding-bottom: 5px; }
    .canvas-container { width: 100%; max-width: 250px; height: 180px; } 
    .full-width { grid-column: span 2; }
    .full-width .canvas-container { max-width: 100%; height: 220px; }

    /* Table Styling */
    .data-table-container { background: white; padding: 20px; border-radius: 10px; border: 1px solid #eee; margin-top: 10px; }
    .summary-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .summary-table th { text-align: left; background: #f8f9fa; padding: 10px; color: #65676b; border-bottom: 2px solid #dee2e6; }
    .summary-table td { padding: 10px; border-bottom: 1px solid #eee; }

    .btn-print { background: #1877f2; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; }

    @media print {
        @page { size: auto; margin: 10mm; }
        body * { visibility: hidden; }
        .analytics-container, .analytics-container * { visibility: visible; }
        .analytics-container { position: absolute; left: 0; top: 0; width: 100%; }
        .btn-print, .sidebar { display: none !important; }
        .analytics-grid { display: block !important; }
        .chart-card { margin-bottom: 20px; page-break-inside: avoid; }
    }
</style>

<div class="analytics-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #1877f2; padding-bottom: 10px;">
        <h2 style="margin:0;"><i class="fa-solid fa-square-poll-vertical"></i> Barangay Analytics Dashboard</h2>
        
        <div style="display: flex; gap: 10px;">
            <a href="dashboard.php" style="text-decoration: none; background: #7be27b; color: #050505; padding: 10px 18px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; border: 1px solid #ddd;">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>

            <button type="button" class="btn-print" onclick="window.print();">
                <i class="fa-solid fa-print"></i> Print PDF Report
            </button>
        </div>
    </div>
   

    <div class="stats-row">
        <div class="stat-card"><h5>Total Residents</h5><h2><?php echo $totalResidents; ?></h2></div>
        <div class="stat-card" style="border-top-color: #31a24c;"><h5>Youth</h5><h2><?php echo $ageData['youth'] ?? 0; ?></h2></div>
        <div class="stat-card" style="border-top-color: #f7b924;"><h5>Seniors</h5><h2><?php echo $ageData['seniors'] ?? 0; ?></h2></div>
        <div class="stat-card" style="border-top-color: #f02849;"><h5>Puroks</h5><h2><?php echo count($purokData); ?></h2></div>
    </div>

    <div class="analytics-grid">
        <div class="chart-card">
            <h4>Gender Ratio</h4>
            <div class="canvas-container"><canvas id="genderChart"></canvas></div>
        </div>
        
        <div class="chart-card">
            <h4>Civil Status</h4>
            <div class="canvas-container"><canvas id="statusChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h4>Age Demographics</h4>
            <div class="canvas-container"><canvas id="ageChart"></canvas></div>
        </div>

<div class="chart-card">
    <h4>Voter Status</h4>
    <div class="canvas-container"><canvas id="sampleChart"></canvas></div>
    <?php if($potentialVoters > 0): ?>
        <p style="font-size: 0.7rem; color: #e41e3f; margin-top: 10px; font-weight: bold;">
            <i class="fa-solid fa-circle-exclamation"></i> 
            <?php echo $potentialVoters; ?> residents are 18+ but not registered.
        </p>
    <?php endif; ?>
</div>

        <div class="chart-card full-width">
            <h4>Population per Purok</h4>
            <div class="canvas-container"><canvas id="purokChart"></canvas></div>
        </div>
    </div>

    <div class="data-table-container">
        <h4 style="margin: 0 0 15px 0; font-size: 0.9rem; color: #1877f2;"><i class="fa-solid fa-list-ol"></i> DETAILED PUROK BREAKDOWN</h4>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Purok Name</th>
                    <th>Resident Count</th>
                    <th>Community Share</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($purokData as $p): 
                    $percent = ($totalResidents > 0) ? round(($p['count'] / $totalResidents) * 100, 1) : 0;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['purok']); ?></strong></td>
                    <td><?php echo $p['count']; ?> Residents</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="background: #eee; width: 100px; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="background: #1877f2; width: <?php echo $percent; ?>%; height: 100%;"></div>
                            </div>
                            <span><?php echo $percent; ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const commonOptions = { 
        maintainAspectRatio: false, 
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } 
    };

    // 1. Gender Chart
    new Chart(document.getElementById('genderChart'), {
        type: 'pie',
        data: {
            labels: [<?php foreach($genderData as $g) echo "'".ucfirst($g['gender'])."',"; ?>],
            datasets: [{
                data: [<?php foreach($genderData as $g) echo $g['count'].","; ?>],
                backgroundColor: ['#1877f2', '#f02849', '#bec3c9']
            }]
        },
        options: commonOptions
    });

    // 2. Civil Status Chart (NEW)
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($statusData as $s) echo "'".ucfirst($s['civil_status'])."',"; ?>],
            datasets: [{
                data: [<?php foreach($statusData as $s) echo $s['count'].","; ?>],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
            }]
        },
        options: commonOptions
    });

    // 3. Age Chart
    new Chart(document.getElementById('ageChart'), {
        type: 'pie',
        data: {
            labels: ['Youth', 'Adults', 'Seniors'],
            datasets: [{
                data: [<?php echo ($ageData['youth'] ?? 0).",".($ageData['adults'] ?? 0).",".($ageData['seniors'] ?? 0); ?>],
                backgroundColor: ['#31a24c', '#f7b924', '#1877f2']
            }]
        },
        options: commonOptions
    });

// 4. Dynamic Voter Status Chart
new Chart(document.getElementById('sampleChart'), {
    type: 'doughnut',
    data: {
        labels: [<?php foreach($voterData as $v) echo "'".$v['voter_status']."',"; ?>],
        datasets: [{
            data: [<?php foreach($voterData as $v) echo $v['count'].","; ?>],
            backgroundColor: ['#1877f2', '#e4e6eb'] // Blue for Registered, Light Gray for Non
        }]
    },
    options: commonOptions
});
    // 5. Purok Chart
    new Chart(document.getElementById('purokChart'), {
        type: 'bar',
        data: {
            labels: [<?php foreach($purokData as $p) echo "'".$p['purok']."',"; ?>],
            datasets: [{
                label: 'Residents',
                data: [<?php foreach($purokData as $p) echo $p['count'].","; ?>],
                backgroundColor: '#1877f2',
                borderRadius: 4
            }]
        },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
});
</script>