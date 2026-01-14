<?php
/**
 * KABAL SOLAR SYSTEM - ADMIN DASHBOARD
 * View and manage quote requests
 * 
 * SECURITY: Change the password below before deployment!
 */

session_start();

// Simple password protection
// ⚠️ CRITICAL SECURITY: CHANGE THIS PASSWORD BEFORE DEPLOYING TO PRODUCTION! ⚠️
// Current password is for DEVELOPMENT ONLY
// For production, use a strong password with: uppercase, lowercase, numbers, symbols
// Minimum 12 characters recommended
define('ADMIN_PASSWORD', 'Kabal@solar1010');  // CHANGE THIS IMMEDIATELY FOR PRODUCTION!

// Handle login
if (isset($_POST['login'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $loginError = 'Invalid password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: view_quotes.php');
    exit;
}

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Kabal Solar System</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Arial', sans-serif;
                background: linear-gradient(135deg, #0A3D62 0%, #27AE60 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: white;
                padding: 2.5rem;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                width: 100%;
                max-width: 400px;
            }
            h2 {
                color: #0A3D62;
                margin-bottom: 1.5rem;
                text-align: center;
            }
            .form-group {
                margin-bottom: 1.5rem;
            }
            label {
                display: block;
                margin-bottom: 0.5rem;
                color: #333;
                font-weight: 600;
            }
            input[type="password"] {
                width: 100%;
                padding: 0.875rem;
                border: 2px solid #E0E0E0;
                border-radius: 8px;
                font-size: 1rem;
            }
            input[type="password"]:focus {
                outline: none;
                border-color: #0A3D62;
            }
            button {
                width: 100%;
                padding: 1rem;
                background: linear-gradient(135deg, #F39C12 0%, #e67e22 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: 0.3s;
            }
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 16px rgba(243, 156, 18, 0.4);
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 0.75rem;
                border-radius: 6px;
                margin-bottom: 1rem;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🌞 Admin Login</h2>
            <?php if (isset($loginError)): ?>
                <div class="error"><?php echo $loginError; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Include configuration
require_once 'config.php';

// Handle status update
if (isset($_POST['update_status'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE clients SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $_POST['status'],
            ':id' => $_POST['client_id']
        ]);
        $successMessage = 'Status updated successfully!';
    } catch (PDOException $e) {
        $errorMessage = 'Error updating status: ' . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->execute([':id' => $_GET['delete']]);
        $successMessage = 'Quote deleted successfully!';
    } catch (PDOException $e) {
        $errorMessage = 'Error deleting quote: ' . $e->getMessage();
    }
}

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterService = isset($_GET['service']) ? $_GET['service'] : 'all';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT * FROM clients WHERE 1=1";
$params = [];

if ($filterStatus !== 'all') {
    $query .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

if ($filterService !== 'all') {
    $query .= " AND service_type = :service";
    $params[':service'] = $filterService;
}

if (!empty($searchTerm)) {
    $query .= " AND (name LIKE :search OR phone LIKE :search OR email LIKE :search)";
    $params[':search'] = '%' . $searchTerm . '%';
}

$query .= " ORDER BY created_at DESC";

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $quotes = $stmt->fetchAll();
    
    // Get statistics
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN service_type = 'solar' THEN 1 ELSE 0 END) as solar,
            SUM(CASE WHEN service_type = 'geyser' THEN 1 ELSE 0 END) as geyser,
            SUM(CASE WHEN property_type = 'residential' THEN 1 ELSE 0 END) as residential,
            SUM(CASE WHEN property_type = 'commercial' THEN 1 ELSE 0 END) as commercial
        FROM clients
    ");
    $stats = $statsStmt->fetch();
    
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
    $quotes = [];
    $stats = ['total' => 0, 'pending' => 0, 'solar' => 0, 'geyser' => 0, 'residential' => 0, 'commercial' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Management - Kabal Solar System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #0A3D62 0%, #0e5a8a 100%);
            color: white;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 1.5rem;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.3s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2rem;
            color: #F39C12;
            margin-bottom: 0.5rem;
        }
        .stat-card h3 {
            font-size: 2rem;
            color: #0A3D62;
            margin-bottom: 0.25rem;
        }
        .stat-card p {
            color: #666;
        }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .filters form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: end;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.625rem;
            border: 2px solid #E0E0E0;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .filter-btn {
            padding: 0.625rem 1.5rem;
            background: #F39C12;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .filter-btn:hover {
            background: #e67e22;
        }
        .quotes-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #0A3D62;
            color: white;
        }
        th, td {
            padding: 1rem;
            text-align: left;
        }
        tbody tr {
            border-bottom: 1px solid #E0E0E0;
            transition: 0.2s;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.813rem;
            font-weight: 600;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-contacted { background: #d1ecf1; color: #0c5460; }
        .badge-quoted { background: #d4edda; color: #155724; }
        .badge-converted { background: #28a745; color: white; }
        .badge-declined { background: #f8d7da; color: #721c24; }
        .badge-solar { background: #F39C12; color: white; }
        .badge-geyser { background: #3498db; color: white; }
        .badge-both { background: #9b59b6; color: white; }
        .action-btn {
            padding: 0.375rem 0.75rem;
            margin: 0 0.25rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.813rem;
            transition: 0.2s;
        }
        .btn-update {
            background: #27AE60;
            color: white;
        }
        .btn-update:hover { background: #229954; }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover { background: #c0392b; }
        .btn-call {
            background: #3498db;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-call:hover { background: #2980b9; }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        .no-quotes {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        @media (max-width: 768px) {
            .filters form { flex-direction: column; }
            .quotes-table { overflow-x: auto; }
            table { min-width: 900px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fa-solid fa-solar-panel"></i> Kabal Solar System - Admin Dashboard</h1>
            <a href="?logout" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($successMessage)): ?>
            <div class="message success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        <?php if (isset($errorMessage)): ?>
            <div class="message error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fa-solid fa-list"></i>
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Quotes</p>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-clock"></i>
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-solar-panel"></i>
                <h3><?php echo $stats['solar']; ?></h3>
                <p>Solar Requests</p>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-droplet"></i>
                <h3><?php echo $stats['geyser']; ?></h3>
                <p>Geyser Requests</p>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-home"></i>
                <h3><?php echo $stats['residential']; ?></h3>
                <p>Residential</p>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-building"></i>
                <h3><?php echo $stats['commercial']; ?></h3>
                <p>Commercial</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Name, phone, email..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all">All Status</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="contacted" <?php echo $filterStatus === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                        <option value="quoted" <?php echo $filterStatus === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                        <option value="converted" <?php echo $filterStatus === 'converted' ? 'selected' : ''; ?>>Converted</option>
                        <option value="declined" <?php echo $filterStatus === 'declined' ? 'selected' : ''; ?>>Declined</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Service</label>
                    <select name="service">
                        <option value="all">All Services</option>
                        <option value="solar" <?php echo $filterService === 'solar' ? 'selected' : ''; ?>>Solar</option>
                        <option value="geyser" <?php echo $filterService === 'geyser' ? 'selected' : ''; ?>>Geyser</option>
                        <option value="both" <?php echo $filterService === 'both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                </div>
                <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
            </form>
        </div>

        <!-- Quotes Table -->
        <div class="quotes-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Service</th>
                        <th>Property</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quotes)): ?>
                        <tr>
                            <td colspan="8" class="no-quotes">
                                <i class="fa-solid fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p style="margin-top: 1rem; font-size: 1.125rem;">No quotes found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td><strong>#<?php echo $quote['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($quote['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($quote['address']); ?></small>
                                    <?php if (!empty($quote['message'])): ?>
                                        <br><small style="color: #666;"><i class="fa-solid fa-comment"></i> <?php echo htmlspecialchars($quote['message']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($quote['phone']); ?>" class="action-btn btn-call">
                                        <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($quote['phone']); ?>
                                    </a>
                                    <?php if (!empty($quote['email'])): ?>
                                        <br><small><?php echo htmlspecialchars($quote['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $quote['service_type']; ?>">
                                        <?php echo ucfirst($quote['service_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($quote['property_type']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="client_id" value="<?php echo $quote['id']; ?>">
                                        <select name="status" class="badge badge-<?php echo $quote['status']; ?>" style="border: none; cursor: pointer;">
                                            <option value="pending" <?php echo $quote['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="contacted" <?php echo $quote['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                            <option value="quoted" <?php echo $quote['status'] === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                                            <option value="converted" <?php echo $quote['status'] === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                            <option value="declined" <?php echo $quote['status'] === 'declined' ? 'selected' : ''; ?>>Declined</option>
                                        </select>
                                        <button type="submit" name="update_status" class="action-btn btn-update">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                </td>
                                <td><small><?php echo date('M d, Y H:i', strtotime($quote['created_at'])); ?></small></td>
                                <td>
                                    <a href="?delete=<?php echo $quote['id']; ?>" 
                                       class="action-btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this quote?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
