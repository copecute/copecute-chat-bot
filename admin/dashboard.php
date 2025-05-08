<?php
require_once 'check_admin.php';

// Lấy thông tin thống kê
try {
    // Tổng số người dùng
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $total_users = $stmt->fetchColumn();
    
    // Tổng số tin nhắn (sử dụng bảng chat_history)
    $stmt = $pdo->query('SELECT COUNT(*) FROM chat_history');
    $total_messages = $stmt->fetchColumn();
    
    // Số người dùng mới trong 7 ngày qua
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $new_users = $stmt->fetchColumn();
    
    // Tổng từ khóa
    $stmt = $pdo->query('SELECT COUNT(*) FROM keywords');
    $total_keywords = $stmt->fetchColumn();
    
    // Người dùng gần đây với thông tin avatar
    $stmt = $pdo->query('
        SELECT u.id, u.username, u.email, u.created_at, ui.avatar 
        FROM users u 
        LEFT JOIN user_infos ui ON u.id = ui.user_id 
        ORDER BY u.created_at DESC LIMIT 5
    ');
    $recent_users = $stmt->fetchAll();
    
    // Tin nhắn gần đây (từ bảng chat_history)
    $stmt = $pdo->query('
        SELECT ch.id, ch.user_input as user_message, ch.bot_response as bot_message, ch.created_at, u.username, ui.avatar
        FROM chat_history ch
        JOIN users u ON ch.user_id = u.id
        LEFT JOIN user_infos ui ON u.id = ui.user_id
        ORDER BY ch.created_at DESC 
        LIMIT 10
    ');
    $recent_messages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Import header
require_once 'layout/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Tổng quan hệ thống</h1>
    <div>
        <button class="btn btn-sm btn-primary shadow-sm" id="refreshStats">
            <i class="fas fa-sync-alt fa-sm me-1"></i> Làm mới dữ liệu
        </button>
        <a href="<?php echo $base_url ?>/chat" class="btn btn-sm btn-info shadow-sm ms-2" target="_blank">
            <i class="fas fa-comment fa-sm me-1"></i> Đến trang chat
        </a>
    </div>
</div>

<!-- Stats cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-card border-primary">
            <h3>Tổng người dùng</h3>
            <div class="number" id="total-users"><?php echo number_format($total_users); ?></div>
            <i class="fas fa-users card-icon text-primary"></i>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-card border-success">
            <h3>Tổng tin nhắn</h3>
            <div class="number" id="total-messages"><?php echo number_format($total_messages); ?></div>
            <i class="fas fa-comments card-icon text-success"></i>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-card border-info">
            <h3>Người dùng mới (7 ngày)</h3>
            <div class="number" id="new-users"><?php echo number_format($new_users); ?></div>
            <i class="fas fa-user-plus card-icon text-info"></i>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-card border-warning">
            <h3>Tổng từ khóa</h3>
            <div class="number" id="total-keywords"><?php echo number_format($total_keywords); ?></div>
            <i class="fas fa-key card-icon text-warning"></i>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Thống kê tin nhắn</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                        <li><a class="dropdown-item" href="#">Theo ngày</a></li>
                        <li><a class="dropdown-item" href="#">Theo tuần</a></li>
                        <li><a class="dropdown-item" href="#">Theo tháng</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Xuất báo cáo</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="messagesAreaChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Phân bố người dùng</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                        <li><a class="dropdown-item" href="#">Phân bố theo giới tính</a></li>
                        <li><a class="dropdown-item" href="#">Phân bố theo tuổi</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Xuất báo cáo</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="usersPieChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Data Row -->
<div class="row">
    <!-- Recent users -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Người dùng gần đây</h6>
                <a href="<?php echo $base_url; ?>/admin/users/index.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-right fa-sm"></i> Xem tất cả
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Người dùng</th>
                                <th>Email</th>
                                <th>Đăng ký</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : '/assets/img/avatar/default.png'; ?>" 
                                            class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                        <div><?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo $base_url; ?>/admin/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit fa-sm"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
                
    <!-- Recent messages -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Tin nhắn gần đây</h6>
                <a href="<?php echo $base_url; ?>/admin/keywords" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-right fa-sm"></i> Quản lý từ khóa
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Người dùng</th>
                                <th>Tin nhắn</th>
                                <th>Phản hồi bot</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_messages as $msg): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($msg['avatar']) ? htmlspecialchars($msg['avatar']) : '/assets/img/avatar/default.png'; ?>" 
                                            class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                        <div><?php echo htmlspecialchars($msg['username']); ?></div>
                                    </div>
                                </td>
                                <td class="text-truncate-2" style="max-width: 200px;">
                                    <?php echo htmlspecialchars($msg['user_message']); ?>
                                </td>
                                <td class="text-truncate-2" style="max-width: 200px;">
                                    <?php echo htmlspecialchars($msg['bot_message']); ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            background-color: white;
            position: relative;
            overflow: hidden;
        }
        .dashboard-card h3 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .dashboard-card .number {
            font-size: 24px;
            font-weight: bold;
        }
        .dashboard-card i.card-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 48px;
            opacity: 0.1;
        }
</style>

<script>
// Hiển thị thời gian hiện tại
function updateTime() {
    const now = new Date();
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = now.toLocaleTimeString();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo biểu đồ Area Chart
    const ctx1 = document.getElementById('messagesAreaChart');
    if (ctx1) {
        const messagesChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ["T1", "T2", "T3", "T4", "T5", "T6", "T7", "T8", "T9", "T10", "T11", "T12"],
                datasets: [{
                    label: "Tin nhắn",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: [0, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 75],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Khởi tạo biểu đồ Pie Chart
    const ctx2 = document.getElementById('usersPieChart');
    if (ctx2) {
        const usersChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ["Nam", "Nữ", "Khác"],
                datasets: [{
                    data: [55, 30, 15],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    // Nút làm mới dữ liệu
    const refreshBtn = document.getElementById('refreshStats');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            // Hiển thị spinner khi đang làm mới
            this.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm me-1"></i> Đang làm mới...';
            this.disabled = true;
            
            // Giả lập làm mới dữ liệu (thực tế sẽ gọi AJAX)
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-sync-alt fa-sm me-1"></i> Làm mới dữ liệu';
                this.disabled = false;
                
                // Hiển thị thông báo
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> Đã cập nhật dữ liệu thành công!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Chèn thông báo vào đầu trang
                const pageContent = document.querySelector('.page-content');
                if (pageContent) {
                    pageContent.insertBefore(alertDiv, pageContent.firstChild);
                }
                
                // Tự động ẩn thông báo sau 3 giây
                setTimeout(() => {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                }, 3000);
            }, 800);
        });
    }
    
    // Cập nhật thời gian
        setInterval(updateTime, 1000);
        updateTime();
});
    </script>

<?php
// Import footer
require_once 'layout/footer.php';
?> 