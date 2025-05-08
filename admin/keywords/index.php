<?php
require_once '../check_admin.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Kiểm tra thêm quyền cho một số chức năng nhất định
$can_manage_keywords = true; // Quản lý và quản trị viên đều có thể quản lý từ khóa

$error = '';
$success = '';

// Xác định loại từ khóa (mặc định là lịch sự)
$type = isset($_GET['type']) && $_GET['type'] === 'impolite' ? 1 : 0;
$type_name = $type ? 'thô lỗ' : 'lịch sự';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Xử lý xóa từ khóa (và tất cả câu trả lời của nó)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Kiểm tra quyền hạn
    if (!$can_manage_keywords) {
        $error = 'Bạn không có quyền xóa từ khóa!';
    } else {
        $keyword_id = $_GET['delete'];
        
        try {
            $pdo->beginTransaction();
            
            // Xóa tất cả câu trả lời của từ khóa
            $stmt = $pdo->prepare("DELETE FROM replies WHERE keyword_id = :keyword_id");
            $stmt->execute(['keyword_id' => $keyword_id]);
            
            // Xóa từ khóa
            $stmt = $pdo->prepare("DELETE FROM keywords WHERE id = :id");
            $stmt->execute(['id' => $keyword_id]);
            
            $pdo->commit();
            $success = 'Đã xóa từ khóa và tất cả câu trả lời của nó thành công!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Lấy dữ liệu từ khóa với phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý tìm kiếm từ khóa
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [$type]; // Tham số đầu tiên cho impolite

try {
    // Xây dựng câu truy vấn cơ bản
    $base_query = "FROM keywords k LEFT JOIN replies r ON k.id = r.keyword_id WHERE k.impolite = ?";
    
    // Thêm điều kiện tìm kiếm nếu có
    if (!empty($search)) {
        $base_query .= " AND (k.keyword LIKE ? OR r.reply LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Truy vấn lấy danh sách từ khóa với số lượng câu trả lời
    $query = "SELECT k.id, k.keyword, k.impolite, COUNT(DISTINCT r.id) as reply_count 
              $base_query
              GROUP BY k.id, k.keyword, k.impolite 
              ORDER BY k.id DESC
              LIMIT ? OFFSET ?";
    
    // Tạo bản sao của mảng tham số cho truy vấn chính
    $main_params = $params;
    $main_params[] = $limit;
    $main_params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($main_params);
    $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy tổng số từ khóa phù hợp với điều kiện tìm kiếm
    $count_query = "SELECT COUNT(DISTINCT k.id) $base_query";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_keywords = $stmt->fetchColumn();
    
    // Tính tổng số trang
    $total_pages = ceil($total_keywords / $limit);
    
} catch (PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
    $keywords = [];
    $total_keywords = 0;
    $total_pages = 1;
}

// Xử lý import từ file JSON
if (isset($_POST['import']) && isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['import_file']['tmp_name'];
    $file_content = file_get_contents($tmp_name);
    $json_data = json_decode($file_content, true);
    
    if ($json_data === null) {
        $error = 'File JSON không hợp lệ. Vui lòng kiểm tra định dạng file.';
    } else {
        try {
            $pdo->beginTransaction();
            $imported_keywords = 0;
            $imported_replies = 0;
            
            foreach ($json_data as $keyword => $replies) {
                // Kiểm tra độ dài từ khóa
                if (strlen($keyword) > 250) {
                    continue; // Bỏ qua từ khóa có độ dài vượt quá 250 ký tự
                }
                
                // Kiểm tra từ khóa đã tồn tại chưa
                $stmt = $pdo->prepare("SELECT id FROM keywords WHERE keyword = :keyword AND impolite = :impolite");
                $stmt->execute([
                    'keyword' => $keyword,
                    'impolite' => $type
                ]);
                $keyword_id = $stmt->fetchColumn();
                
                if (!$keyword_id) {
                    // Thêm từ khóa mới
                    $stmt = $pdo->prepare("INSERT INTO keywords (keyword, impolite) VALUES (:keyword, :impolite)");
                    $stmt->execute([
                        'keyword' => $keyword,
                        'impolite' => $type
                    ]);
                    $keyword_id = $pdo->lastInsertId();
                    $imported_keywords++;
                }
                
                // Thêm các câu trả lời
                foreach ($replies as $reply) {
                    // Kiểm tra độ dài câu trả lời
                    if (strlen($reply) > 250) {
                        continue; // Bỏ qua câu trả lời có độ dài vượt quá 250 ký tự
                    }
                    
                    // Kiểm tra câu trả lời đã tồn tại chưa
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM replies WHERE keyword_id = :keyword_id AND reply = :reply");
                    $stmt->execute([
                        'keyword_id' => $keyword_id,
                        'reply' => $reply
                    ]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        // Thêm câu trả lời mới
                        $stmt = $pdo->prepare("INSERT INTO replies (keyword_id, reply) VALUES (:keyword_id, :reply)");
                        $stmt->execute([
                            'keyword_id' => $keyword_id,
                            'reply' => $reply
                        ]);
                        $imported_replies++;
                    }
                }
            }
            
            $pdo->commit();
            $success = "Đã nhập thành công $imported_keywords từ khóa mới và $imported_replies câu trả lời.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Bao gồm header
include_once('../layout/header.php');
?>

<style>
    .nav-pills .nav-link:not(.active) {
        border: 1px solid #dee2e6;
        color: #007bff;
        background-color: #fff;
    }
    .nav-pills .nav-link.active {
        color: #fff;
        background-color: #007bff;
    }
</style>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý Từ khóa và Phản hồi</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Từ khóa & Phản hồi</li>
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

<!-- Filter & Actions Cards -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Lọc và Tìm kiếm</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <form action="" method="get" class="d-flex w-100">
                                <input type="hidden" name="type" value="<?php echo $type ? 'impolite' : 'polite'; ?>">
                                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm từ khóa..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Tìm
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $type == 0 ? 'active' : ''; ?>" href="?type=polite<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    Từ khóa lịch sự
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $type == 1 ? 'active' : ''; ?>" href="?type=impolite<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    Từ khóa thô lỗ
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Thao tác</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="<?php echo $base_url; ?>/admin/keywords/add.php?type=<?php echo $type ? 'impolite' : 'polite'; ?>" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Thêm từ khóa
                    </a>
                    <a href="<?php echo $base_url; ?>/admin/keywords/export.php?type=<?php echo $type ? 'impolite' : 'polite'; ?>" class="btn btn-info">
                        <i class="fas fa-file-export"></i> Xuất JSON
                    </a>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-import"></i> Nhập JSON
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng dữ liệu -->
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Danh sách từ khóa <?php echo $type_name; ?></h6>
        <span class="badge bg-primary"><?php echo $total_keywords; ?> từ khóa</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="60">ID</th>
                        <th>Từ khóa</th>
                        <th width="100">Phản hồi</th>
                        <th width="160">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keywords)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-search fa-2x mb-3"></i>
                                <p>Không tìm thấy từ khóa nào</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($keywords as $keyword): ?>
                        <tr>
                            <td><?php echo $keyword['id']; ?></td>
                            <td><?php echo htmlspecialchars($keyword['keyword']); ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?php echo $keyword['reply_count']; ?></span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?php echo $base_url; ?>/admin/keywords/view.php?id=<?php echo $keyword['id']; ?>&type=<?php echo $type ? 'impolite' : 'polite'; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo $base_url; ?>/admin/keywords/edit.php?id=<?php echo $keyword['id']; ?>&type=<?php echo $type ? 'impolite' : 'polite'; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $keyword['id']; ?>)" data-bs-toggle="tooltip" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $type ? 'impolite' : 'polite'; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1&type='.($type ? 'impolite' : 'polite').(!empty($search) ? '&search='.urlencode($search) : '').'">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li class="page-item '.($page == $i ? 'active' : '').'">
                        <a class="page-link" href="?page='.$i.'&type='.($type ? 'impolite' : 'polite').(!empty($search) ? '&search='.urlencode($search) : '').'">'.$i.'</a>
                    </li>';
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'&type='.($type ? 'impolite' : 'polite').(!empty($search) ? '&search='.urlencode($search) : '').'">'.$total_pages.'</a></li>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $type ? 'impolite' : 'polite'; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Nhập từ khóa từ file JSON</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Chọn file JSON</label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".json" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> File JSON phải có định dạng: {"từ khóa 1": ["câu trả lời 1", "câu trả lời 2"], "từ khóa 2": [...]}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="import" class="btn btn-primary">Nhập</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm('Bạn có chắc chắn muốn xóa từ khóa này và tất cả câu trả lời của nó không?')) {
            window.location.href = '?delete=' + id + '&type=<?php echo $type ? 'impolite' : 'polite'; ?>';
        }
    }
</script>

<?php include_once('../layout/footer.php'); ?> 