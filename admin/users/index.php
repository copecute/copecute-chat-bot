<?php
require_once '../check_admin.php';

$error = '';
$success = '';

// Xử lý xóa người dùng
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    // Không cho phép xóa tài khoản admin
    if ($user_id == 1) {
        $error = 'Không thể xóa tài khoản admin!';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Xóa từ bảng user_tokens
            $stmt = $pdo->prepare('DELETE FROM user_tokens WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            // Xóa từ bảng user_infos
            $stmt = $pdo->prepare('DELETE FROM user_infos WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            // Xóa từ bảng chat_history
            $stmt = $pdo->prepare('DELETE FROM chat_history WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            // Cuối cùng xóa từ bảng users
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            $pdo->commit();
            $success = 'Đã xóa người dùng thành công!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Xử lý reset token/quota
if (isset($_GET['reset_token']) && is_numeric($_GET['reset_token'])) {
    $user_id = $_GET['reset_token'];
    try {
        // Kiểm tra người dùng có tồn tại không
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        if ($stmt->fetchColumn() > 0) {
            // Tạo token mới
            $token = bin2hex(random_bytes(32));
            
            // Kiểm tra token đã tồn tại chưa
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_tokens WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            
            if ($stmt->fetchColumn() > 0) {
                // Cập nhật token và quota
                $stmt = $pdo->prepare('UPDATE user_tokens SET token = :token, quota = 100 WHERE user_id = :user_id');
                $stmt->execute([
                    'token' => $token,
                    'user_id' => $user_id
                ]);
            } else {
                // Tạo mới token nếu chưa có
                $stmt = $pdo->prepare('INSERT INTO user_tokens (user_id, token, quota) VALUES (:user_id, :token, 100)');
                $stmt->execute([
                    'user_id' => $user_id,
                    'token' => $token
                ]);
            }
            
            $success = 'Đã reset token và quota thành công!';
        } else {
            $error = 'Không tìm thấy người dùng!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Thêm xử lý reset quota và toggle status
if (isset($_GET['reset_quota']) && is_numeric($_GET['reset_quota'])) {
    $user_id = $_GET['reset_quota'];
    try {
        // Kiểm tra người dùng có tồn tại không
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        if ($stmt->fetchColumn() > 0) {
            // Reset quota về mặc định (100)
            $stmt = $pdo->prepare('UPDATE user_tokens SET quota = 100 WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
            $success = 'Đã reset quota thành công!';
        } else {
            $error = 'Không tìm thấy người dùng!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

if (isset($_GET['toggle_status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];
    $status = $_GET['toggle_status'];
    
    try {
        // Kiểm tra và cập nhật trạng thái
        $stmt = $pdo->prepare('SELECT is_acctive FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        $current_status = $stmt->fetchColumn();
        
        if ($current_status !== false) {
            $new_status = $status;
            $stmt = $pdo->prepare('UPDATE users SET is_acctive = :status WHERE id = :user_id');
            $stmt->execute([
                'status' => $new_status,
                'user_id' => $user_id
            ]);
            $success = 'Đã ' . ($new_status == '1' ? 'mở khóa' : 'khóa') . ' tài khoản thành công!';
        } else {
            $error = 'Không tìm thấy người dùng!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Lấy danh sách người dùng có phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý tìm kiếm và lọc
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // Xây dựng các phần của truy vấn
    $select_fields = "u.id, u.username, u.email, u.created_at, u.level as is_admin, 
                      u.is_acctive as is_active, u.last_login, 
                      ui.full_name, ui.avatar, 
                      ut.quota as quota_limit, 
                      COALESCE((SELECT COUNT(*) FROM chat_history WHERE user_id = u.id), 0) as quota_used";
                      
    $from_tables = "users u
                   LEFT JOIN user_infos ui ON u.id = ui.user_id
                   LEFT JOIN user_tokens ut ON u.id = ut.user_id";
                   
    $where_conditions = ["1=1"]; // Luôn bắt đầu với điều kiện true
    $params = [];
    
    // Thêm điều kiện tìm kiếm
    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR ui.full_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Thêm điều kiện lọc theo vai trò
    if ($role !== '') {
        $where_conditions[] = "u.level = ?";
        $params[] = $role;
    }
    
    // Thêm điều kiện lọc theo trạng thái
    if ($status !== '') {
        if ($status === 'temp_lock') {
            // Tìm các tài khoản bị khóa tạm thời (is_acctive là một ngày trong tương lai)
            $where_conditions[] = "u.is_acctive REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND u.is_acctive > CURDATE()";
        } else {
            $where_conditions[] = "u.is_acctive = ?";
            $params[] = $status;
        }
    }
    
    // Tạo câu WHERE
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Đếm tổng số bản ghi
    $count_query = "SELECT COUNT(DISTINCT u.id) FROM $from_tables $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);
    
    // Truy vấn chính với phân trang
    $main_query = "SELECT $select_fields FROM $from_tables $where_clause ORDER BY u.id DESC LIMIT ? OFFSET ?";
    $main_stmt = $pdo->prepare($main_query);
    
    // Thêm tham số phân trang vào cuối mảng params
    $all_params = array_merge($params, [$limit, $offset]);
    
    $main_stmt->execute($all_params);
    $users = $main_stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
    $total_users = 0;
    $total_pages = 1;
    $users = [];
}

// Import header
require_once '../layout/header.php';
?>

<!-- Thêm SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý người dùng</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Quản lý người dùng</li>
        </ol>
    </nav>
</div>

<!-- Thông báo -->
<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Filter & Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select">
                            <option value="">- Tất cả vai trò -</option>
                            <option value="0" <?php echo isset($_GET['role']) && $_GET['role'] === '0' ? 'selected' : ''; ?>>Người dùng</option>
                            <option value="1" <?php echo isset($_GET['role']) && $_GET['role'] === '1' ? 'selected' : ''; ?>>Quản lý</option>
                            <option value="2" <?php echo isset($_GET['role']) && $_GET['role'] === '2' ? 'selected' : ''; ?>>Quản trị viên</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">- Tất cả trạng thái -</option>
                            <option value="1" <?php echo isset($_GET['status']) && $_GET['status'] === '1' ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="0" <?php echo isset($_GET['status']) && $_GET['status'] === '0' ? 'selected' : ''; ?>>Chưa kích hoạt</option>
                            <option value="2" <?php echo isset($_GET['status']) && $_GET['status'] === '2' ? 'selected' : ''; ?>>Bị khóa vĩnh viễn</option>
                            <option value="temp_lock" <?php echo isset($_GET['status']) && $_GET['status'] === 'temp_lock' ? 'selected' : ''; ?>>Bị khóa tạm thời</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i> Lọc
                        </button>
                    </div>
        </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-grid">
                    <a href="<?php echo $base_url; ?>/admin/users/add.php" class="btn btn-success">
                        <i class="fas fa-user-plus me-2"></i> Thêm người dùng mới
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Danh sách người dùng -->
<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary">
                <i class="fas fa-users me-2"></i> Danh sách người dùng
            </h5>
            <span class="badge bg-primary"><?php echo $total_users; ?> người dùng</span>
        </div>
    </div>
    <div class="card-body">
<div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" width="60">ID</th>
                        <th scope="col">Tên người dùng</th>
                        <th scope="col">Email</th>
                        <th scope="col" width="120">Vai trò</th>
                        <th scope="col" width="120">Trạng thái</th>
                        <th scope="col" width="120">Quota</th>
                        <th scope="col" width="150">Thao tác</th>
            </tr>
        </thead>
        <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p class="mb-0">Không tìm thấy người dùng nào</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($user['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                             class="rounded-circle me-2" 
                                             width="32" height="32" 
                                             alt="<?php echo htmlspecialchars($user['username']); ?>">
                                    <?php else: ?>
                                        <img src="/assets/img/avatar/default.png" 
                                             class="rounded-circle me-2" 
                                             width="32" height="32" 
                                             alt="Default Avatar">
                    <?php endif; ?>
                    <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                </td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    if ($user['is_admin'] == 2) echo 'primary';
                                    elseif ($user['is_admin'] == 1) echo 'info';
                                    else echo 'secondary';
                                ?>">
                                    <?php 
                                    if ($user['is_admin'] == 2) echo 'Quản trị viên';
                                    elseif ($user['is_admin'] == 1) echo 'Quản lý';
                                    else echo 'Người dùng';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    if ($user['is_active'] === '0') {
                                        echo 'warning';
                                    } elseif ($user['is_active'] === '1') {
                                        echo 'success';
                                    } elseif ($user['is_active'] === '2') {
                                        echo 'danger';
                                    } else {
                                        // Kiểm tra nếu là ngày (định dạng Y-m-d)
                                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $user['is_active'])) {
                                            $lock_date = DateTime::createFromFormat('Y-m-d', $user['is_active']);
                                            $now = new DateTime();
                                            if ($lock_date > $now) {
                                                echo 'danger';
                                            } else {
                                                // Tự động mở khóa nếu đã hết hạn
                                                try {
                                                    $stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                                                    $stmt->execute(['id' => $user['id']]);
                                                    echo 'success';
                                                } catch (PDOException $e) {
                                                    echo 'danger';
                                                }
                                            }
                                        } else {
                                            echo 'secondary';
                                        }
                                    }
                                ?>">
                                    <?php 
                                    if ($user['is_active'] === '0') {
                                        echo 'Chưa kích hoạt';
                                    } elseif ($user['is_active'] === '1') {
                                        echo 'Đang hoạt động';
                                    } elseif ($user['is_active'] === '2') {
                                        echo 'Đã khóa vĩnh viễn';
                                    } else {
                                        // Kiểm tra nếu là ngày (định dạng Y-m-d)
                                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $user['is_active'])) {
                                            $lock_date = DateTime::createFromFormat('Y-m-d', $user['is_active']);
                                            $now = new DateTime();
                                            if ($lock_date > $now) {
                                                // Chuyển đổi sang định dạng hiển thị d/m/Y
                                                $display_date = $lock_date->format('d/m/Y');
                                                echo 'Bị khóa đến ' . $display_date;
                                            } else {
                                                // Tự động mở khóa nếu đã hết hạn
                                                try {
                                                    $stmt = $pdo->prepare('UPDATE users SET is_acctive = "1" WHERE id = :id');
                                                    $stmt->execute(['id' => $user['id']]);
                                                    echo 'Đang hoạt động';
                                                } catch (PDOException $e) {
                                                    echo 'Lỗi cập nhật trạng thái';
                                                }
                                            }
                                        } else {
                                            echo 'Không xác định (' . $user['is_active'] . ')';
                                        }
                                    }
                                    ?>
                        </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                        <?php 
                                        $quota_used = $user['quota_used'] ?? 0;
                                        $quota_limit = $user['quota_limit'] ?? 100;
                                        $quota_percent = $quota_limit > 0 ? ($quota_used / $quota_limit) * 100 : 0;
                                        $quota_color = $quota_percent > 80 ? 'danger' : ($quota_percent > 50 ? 'warning' : 'success');
                                        ?>
                                        <div class="progress-bar bg-<?php echo $quota_color; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $quota_percent; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $quota_used; ?>/<?php echo $quota_limit; ?></small>
                                </div>
                </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo $base_url; ?>/admin/users/edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       data-bs-toggle="tooltip" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['is_active'] === '1'): ?>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning" 
                                                    onclick="showLockModal(<?php echo $user['id']; ?>)"
                                                    data-bs-toggle="tooltip" 
                                                    title="Khóa tài khoản">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif ($user['is_active'] === '0' || $user['is_active'] === '2' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $user['is_active'])): ?>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    onclick="toggleStatus(<?php echo $user['id']; ?>, '1')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Kích hoạt">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-info" 
                                            onclick="resetQuota(<?php echo $user['id']; ?>)"
                                            data-bs-toggle="tooltip" 
                                            title="Reset quota">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    <?php if (!$user['is_admin']): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?php echo $user['id']; ?>)"
                                                data-bs-toggle="tooltip" 
                                                title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                    <?php endif; ?>
                                </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page - 1) . $query_string; ?>">
                        <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>
        
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1' . $query_string . '">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                            <a class="page-link" href="?page=' . $i . $query_string . '">' . $i . '</a>
                          </li>';
                }

                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . $query_string . '">' . $total_pages . '</a></li>';
                }
                ?>
        
        <?php if ($page < $total_pages): ?>
        <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page + 1) . $query_string; ?>">
                        <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
    </div>
</div>

<!-- Thêm Modal Khóa tài khoản -->
<div class="modal fade" id="lockModal" tabindex="-1" aria-labelledby="lockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lockModalLabel">Khóa tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="lockForm" method="get">
                    <input type="hidden" name="id" id="lockUserId">
                    <div class="mb-3">
                        <label class="form-label">Chọn loại khóa:</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="lock_type" id="lockTemporary" value="temporary" checked>
                            <label class="form-check-label" for="lockTemporary">
                                <i class="fas fa-clock me-2"></i> Khóa tạm thời
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="lock_type" id="lockPermanent" value="permanent">
                            <label class="form-check-label" for="lockPermanent">
                                <i class="fas fa-lock me-2"></i> Khóa vĩnh viễn
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="lock_type" id="lockInactive" value="inactive">
                            <label class="form-check-label" for="lockInactive">
                                <i class="fas fa-ban me-2"></i> Chuyển về chưa kích hoạt
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="lockDateGroup">
                        <label for="lockDate" class="form-label">Chọn ngày mở khóa:</label>
                        <input type="date" class="form-control" id="lockDate" name="lock_date" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="submitLock()">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.card {
    transition: all 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.progress {
    background-color: #e9ecef;
}

.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
function handleUserAction(action, userId, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('user_id', userId);
    
    // Thêm các dữ liệu bổ sung nếu có
    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }
    
    fetch('<?php echo $base_url; ?>/admin/users/user-control.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            Swal.fire({
                title: 'Thành công!',
                text: result.message,
                icon: 'success'
            }).then(() => {
                // Reload trang để cập nhật dữ liệu
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Lỗi!',
                text: result.message,
                icon: 'error'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Lỗi!',
            text: 'Có lỗi xảy ra khi xử lý yêu cầu',
            icon: 'error'
        });
    });
}

function showLockModal(userId) {
    // Lưu user ID vào input hidden
    document.getElementById('lockUserId').value = userId;
    // Hiển thị modal
    const modal = new bootstrap.Modal(document.getElementById('lockModal'));
    modal.show();
}

function submitLock() {
    const userId = document.getElementById('lockUserId').value;
    const lockType = document.querySelector('input[name="lock_type"]:checked').value;
    
    if (lockType === 'temporary') {
        const lockDate = document.getElementById('lockDate').value;
        if (!lockDate) {
            Swal.fire({
                title: 'Lỗi!',
                text: 'Vui lòng chọn ngày mở khóa',
                icon: 'error'
            });
            return;
        }
        handleUserAction('lock', userId, {
            lock_type: lockType,
            lock_date: lockDate
        });
    } else {
        handleUserAction('lock', userId, {
            lock_type: lockType
        });
    }
    
    // Đóng modal
    const lockModal = bootstrap.Modal.getInstance(document.getElementById('lockModal'));
    lockModal.hide();
}

function toggleStatus(userId, newStatus) {
    if (newStatus === '1') {
        Swal.fire({
            title: 'Xác nhận mở khóa?',
            text: 'Bạn có chắc chắn muốn mở khóa tài khoản này không?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Mở khóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                handleUserAction('unlock', userId);
            }
        });
    }
}

function resetQuota(userId) {
    Swal.fire({
        title: 'Xác nhận reset quota?',
        text: 'Bạn có chắc chắn muốn đặt lại quota về mặc định không?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Reset',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            handleUserAction('reset_quota', userId);
        }
    });
}

function confirmDelete(userId) {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: 'Bạn có chắc chắn muốn xóa tài khoản này? Hành động này không thể hoàn tác!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            handleUserAction('delete', userId);
        }
    });
}

// Xử lý hiển thị/ẩn trường ngày khóa
document.addEventListener('DOMContentLoaded', function() {
    const lockTemporary = document.getElementById('lockTemporary');
    const lockPermanent = document.getElementById('lockPermanent');
    const lockInactive = document.getElementById('lockInactive');
    const lockDateGroup = document.getElementById('lockDateGroup');

    if (lockTemporary && lockPermanent && lockInactive && lockDateGroup) {
        lockTemporary.addEventListener('change', function() {
            lockDateGroup.style.display = 'block';
        });

        lockPermanent.addEventListener('change', function() {
            lockDateGroup.style.display = 'none';
        });

        lockInactive.addEventListener('change', function() {
            lockDateGroup.style.display = 'none';
        });
    }

    // Khởi tạo tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
// Import footer
require_once '../layout/footer.php';
?> 