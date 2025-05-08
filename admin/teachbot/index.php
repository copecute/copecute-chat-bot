<?php
require_once '../check_admin.php';

// Kiểm tra quyền quản lý từ khóa
$can_manage_teachbot = $is_admin || $_SESSION['level'] >= 1; // Cả quản trị viên và quản lý đều có quyền

if (!$can_manage_teachbot) {
    header('Location: ' . $base_url . '/admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Lấy danh sách yêu cầu dạy bot với phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý lọc trạng thái
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Xây dựng các phần của truy vấn
    $where_conditions = [];
    $params = [];
    
    // Lọc theo trạng thái
    if ($status_filter !== 'all') {
        $where_conditions[] = "tr.status = ?";
        $params[] = $status_filter;
    }
    
    // Thêm điều kiện tìm kiếm
    if (!empty($search)) {
        $where_conditions[] = "(tr.keyword LIKE ? OR tr.reply LIKE ? OR u.username LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Tạo câu WHERE
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Truy vấn lấy danh sách yêu cầu dạy bot
    $query = "
        SELECT tr.*, u.username, u.email 
        FROM teachbot_requests tr
        LEFT JOIN users u ON tr.user_id = u.id
        $where_clause
        ORDER BY tr.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Thêm tham số phân trang
    $all_params = array_merge($params, [$limit, $offset]);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($all_params);
    $requests = $stmt->fetchAll();
    
    // Đếm tổng số bản ghi
    $count_query = "
        SELECT COUNT(*) 
        FROM teachbot_requests tr
        LEFT JOIN users u ON tr.user_id = u.id
        $where_clause
    ";
    
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_requests = $stmt->fetchColumn();
    
    // Tính tổng số trang
    $total_pages = ceil($total_requests / $limit);
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
    $requests = [];
    $total_requests = 0;
    $total_pages = 1;
}

// Tạo query string cho phân trang
$query_string = "";
if (!empty($search)) {
    $query_string .= "&search=" . urlencode($search);
}
if ($status_filter !== 'pending') {
    $query_string .= "&status=" . urlencode($status_filter);
}

// Bao gồm header
include_once('../layout/header.php');
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý Dạy Bot</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dạy Bot</li>
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

<!-- Filter card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <form action="" method="get" class="d-flex">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm từ khóa, nội dung..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <div class="btn-group" role="group">
                        <a href="?status=pending<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-clock me-1"></i> Đang chờ duyệt
                        </a>
                        <a href="?status=approved<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="btn <?php echo $status_filter === 'approved' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-check me-1"></i> Đã duyệt
                        </a>
                        <a href="?status=rejected<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="btn <?php echo $status_filter === 'rejected' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-times me-1"></i> Đã từ chối
                        </a>
                        <a href="?status=all<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-list me-1"></i> Tất cả
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thêm thông tin về chức năng chỉnh sửa vào trang -->
<!-- Bảng danh sách yêu cầu -->
<div class="card shadow-sm mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Danh sách yêu cầu dạy bot</h6>
        <span class="badge bg-primary"><?php echo $total_requests; ?> yêu cầu</span>
    </div>
    <div class="card-body">
        <?php if ($status_filter === 'pending'): ?>
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i> 
            Quản trị viên và quản lý có thể chỉnh sửa từ khóa (tối đa 250 ký tự), câu trả lời và đánh dấu thô lỗ trước khi duyệt bằng cách xem chi tiết yêu cầu.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        <?php if (empty($requests)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-robot fa-3x mb-3"></i>
            <p>Không có yêu cầu dạy bot nào<?php echo $status_filter !== 'all' ? ' ở trạng thái này' : ''; ?></p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="60">ID</th>
                        <th>Từ khóa</th>
                        <th>Câu trả lời</th>
                        <th>Người gửi</th>
                        <th width="90">Thô lỗ</th>
                        <th width="120">Trạng thái</th>
                        <th width="120">Ngày gửi</th>
                        <th width="120">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo $request['id']; ?></td>
                        <td><?php echo htmlspecialchars($request['keyword']); ?></td>
                        <td class="text-wrap"><?php echo nl2br(htmlspecialchars($request['reply'])); ?></td>
                        <td>
                            <a href="<?php echo $base_url; ?>/admin/users/edit.php?id=<?php echo $request['user_id']; ?>" target="_blank">
                                <?php echo htmlspecialchars($request['username']); ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <?php if ($request['impolite']): ?>
                            <span class="badge bg-danger"><i class="fas fa-check"></i></span>
                            <?php else: ?>
                            <span class="badge bg-success"><i class="fas fa-times"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $request['status'] === 'pending' ? 'warning' : 
                                    ($request['status'] === 'approved' ? 'success' : 'danger'); 
                            ?>">
                                <?php 
                                echo $request['status'] === 'pending' ? 'Đang chờ duyệt' : 
                                    ($request['status'] === 'approved' ? 'Đã duyệt' : 'Đã từ chối'); 
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                            <div class="btn-group">
                                <a href="<?php echo $base_url; ?>/admin/teachbot/view.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-success" onclick="approveRequest(<?php echo $request['id']; ?>)" data-bs-toggle="tooltip" title="Duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal(<?php echo $request['id']; ?>)" data-bs-toggle="tooltip" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <a href="<?php echo $base_url; ?>/admin/teachbot/view.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1'.$query_string.'">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li class="page-item '.($page == $i ? 'active' : '').'">
                        <a class="page-link" href="?page='.$i.$query_string.'">'.$i.'</a>
                    </li>';
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.$query_string.'">'.$total_pages.'</a></li>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Từ chối -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Từ chối yêu cầu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <input type="hidden" id="requestId" name="requestId">
                    <div class="mb-3">
                        <label for="rejectReason" class="form-label">Lý do từ chối:</label>
                        <textarea class="form-control" id="rejectReason" name="rejectReason" rows="3" placeholder="Nhập lý do từ chối (tùy chọn)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="rejectRequest()">Từ chối</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Khởi tạo tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

// Hàm duyệt yêu cầu
function approveRequest(id) {
    Swal.fire({
        title: 'Xác nhận duyệt',
        text: 'Bạn có chắc chắn muốn duyệt yêu cầu này?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Duyệt',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            // Gửi yêu cầu duyệt
            fetch('<?php echo $base_url; ?>/admin/teachbot/process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=approve&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Thành công!',
                        text: data.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Đã xảy ra lỗi khi xử lý yêu cầu',
                    icon: 'error'
                });
            });
        }
    });
}

// Hiển thị modal từ chối
function showRejectModal(id) {
    document.getElementById('requestId').value = id;
    var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

// Hàm từ chối yêu cầu
function rejectRequest() {
    var id = document.getElementById('requestId').value;
    var reason = document.getElementById('rejectReason').value;
    
    // Gửi yêu cầu từ chối
    fetch('<?php echo $base_url; ?>/admin/teachbot/process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=reject&id=' + id + '&reason=' + encodeURIComponent(reason)
    })
    .then(response => response.json())
    .then(data => {
        // Đóng modal
        var rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
        rejectModal.hide();
        
        if (data.status === 'success') {
            Swal.fire({
                title: 'Thành công!',
                text: data.message,
                icon: 'success'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Lỗi!',
                text: data.message,
                icon: 'error'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Lỗi!',
            text: 'Đã xảy ra lỗi khi xử lý yêu cầu',
            icon: 'error'
        });
    });
}
</script>

<?php include_once('../layout/footer.php'); ?> 