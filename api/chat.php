<?php
require_once 'config.php';

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Phương thức không được hỗ trợ', 405);
}

// Lấy token từ header Authorization
$token = getAuthToken();
if (!$token) {
    returnError('Không tìm thấy token xác thực', 401);
}

// Xác thực token
$user_id = validateApiToken($token, $pdo);
if (!$user_id) {
    returnError('Token không hợp lệ hoặc đã hết hạn', 401);
}

// Nhận dữ liệu JSON từ client
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($input['message']) || trim($input['message']) === '') {
    returnError('Tin nhắn không được để trống');
}

$user_input = trim($input['message']);

// Kiểm tra độ dài tin nhắn
if (strlen($user_input) > 250) {
    returnSuccess([
        'bot_response' => 'Bạn nói dài quá, tóm tắt lại được không?',
        'is_used_quota' => false
    ]);
    exit;
}

try {
    // Kiểm tra user tồn tại
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $user_exists = $stmt->fetch();
    
    if (!$user_exists) {
        returnError('Tài khoản không tồn tại hoặc đã bị xóa', 404);
    }
    
    // Lấy quota của người dùng
    $stmt = $pdo->prepare('SELECT quota FROM user_tokens WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $token_info = $stmt->fetch();
    
    if (!$token_info) {
        returnError('Không tìm thấy thông tin token', 404);
    }
    
    $quota = $token_info['quota'];
    
    // Lấy cài đặt từ system_settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('out_of_quota_message')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Kiểm tra quota trước khi xử lý
    if ($quota <= 0) {
        // Tạo tin nhắn thông báo hết quota từ cài đặt hệ thống
        $bot_response = $settings['out_of_quota_message'] ?? 'Ôi nooo! Hết quota chat rồi hả bạn iu? Buồn quá xá luôn á! 🥺 Vậy chắc tớ phải "off" đây. Mong sớm được "nâng cấp" để lại được trò chuyện cùng bạn nha! Nhớ bạn nhìu nhìu! Bye bye! 👋💖';
        
        returnSuccess([
            'bot_response' => $bot_response,
            'quota' => 0,
            'is_used_quota' => false
        ]);
        exit;
    }
    
    // Xử lý tin nhắn và lấy phản hồi từ bot
    $bot_response = getBotResponse($user_input, $pdo);
    $is_used_quota = true;
    
    // Kiểm tra xem có phải là default response không
    $default_responses = [
        "Xin lỗi, tôi không hiểu câu hỏi của bạn.",
        "Tôi chưa được học câu trả lời cho câu hỏi này.",
        "Bạn có thể hỏi điều gì khác không?",
        "Tôi là chatbot đơn giản, vấn đề này hơi phức tạp với tôi.",
        "Hãy thử hỏi tôi điều gì đó khác nhé!"
    ];
    
    $is_default_response = in_array($bot_response, $default_responses);

    if (!$is_default_response) {
        // Lưu vào lịch sử chat khi không phải default response
        $stmt = $pdo->prepare('INSERT INTO chat_history (user_id, user_input, bot_response) VALUES (:user_id, :user_input, :bot_response)');
        $stmt->execute([
            'user_id' => $user_id,
            'user_input' => $user_input,
            'bot_response' => $bot_response
        ]);
        
        // Trừ quota
        $stmt = $pdo->prepare('UPDATE user_tokens SET quota = quota - 1 WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        
        // Lấy quota mới
        $stmt = $pdo->prepare('SELECT quota FROM user_tokens WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        $token_info = $stmt->fetch();
        $quota = $token_info['quota'];
    } else {
        $is_used_quota = false;
    }
    
    // Trả về kết quả
    returnSuccess([
        'bot_response' => $bot_response,
        'quota' => $quota,
        'is_default_response' => $is_default_response,
        'is_used_quota' => $is_used_quota
    ]);
    
} catch (PDOException $e) {
    returnError('Lỗi hệ thống: ' . $e->getMessage(), 500);
}

// Hàm xử lý tin nhắn và tạo phản hồi
function getBotResponse($input, $pdo) {
    // Làm sạch đầu vào
    $input = strtolower(trim($input));
    
    try {
        // 1. Đầu tiên tìm từ khóa khớp chính xác
        $stmt = $pdo->prepare('
            SELECT r.reply, k.keyword
            FROM replies r
            JOIN keywords k ON r.keyword_id = k.id
            WHERE LOWER(k.keyword) = LOWER(:input)
            ORDER BY RAND() 
            LIMIT 1
        ');
        $stmt->execute(['input' => $input]);
        $exact_match = $stmt->fetch();
        
        if ($exact_match) {
            return $exact_match['reply'];
        }
        
        // 2. Nếu không tìm thấy từ khóa chính xác, tìm từ khóa là một từ duy nhất trong câu hỏi
        // Lấy tất cả từ khóa từ database
        $stmt = $pdo->query('SELECT id, keyword FROM keywords ORDER BY LENGTH(keyword) DESC');
        $all_keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $matched_keyword_id = null;
        $matched_keyword = null;
        $max_length = 0;
        
        // Xử lý tách từ và tìm kiếm từng từ trong input
        $words = preg_split('/\s+/', $input);
        
        foreach ($all_keywords as $keyword_data) {
            $keyword = strtolower($keyword_data['keyword']);
            
            // Trường hợp 1: Nếu từ khóa là một từ đơn và nằm trong danh sách các từ trong câu hỏi
            if (in_array($keyword, $words)) {
                if (strlen($keyword) > $max_length) {
                    $max_length = strlen($keyword);
                    $matched_keyword_id = $keyword_data['id'];
                    $matched_keyword = $keyword;
                }
            }
            // Trường hợp 2: Nếu từ khóa là cụm từ và nằm trong câu hỏi
            elseif (strpos($input, $keyword) !== false) {
                if (strlen($keyword) > $max_length) {
                    $max_length = strlen($keyword);
                    $matched_keyword_id = $keyword_data['id'];
                    $matched_keyword = $keyword;
                }
            }
        }
        
        // Nếu tìm thấy từ khóa phù hợp, lấy câu trả lời lịch sự
        if ($matched_keyword_id) {
            $stmt = $pdo->prepare('
                SELECT r.reply
                FROM replies r
                WHERE r.keyword_id = :keyword_id
                AND r.impolite = 0
                ORDER BY RAND()
                LIMIT 1
            ');
            $stmt->execute(['keyword_id' => $matched_keyword_id]);
            $match = $stmt->fetch();
            
            if ($match) {
                return $match['reply'];
            }
            
            // Nếu không có câu trả lời lịch sự, thử trả lời thô lỗ
            $stmt = $pdo->prepare('
                SELECT r.reply
                FROM replies r
                WHERE r.keyword_id = :keyword_id
                AND r.impolite = 1
                ORDER BY RAND()
                LIMIT 1
            ');
            $stmt->execute(['keyword_id' => $matched_keyword_id]);
            $impolite_match = $stmt->fetch();
            
            if ($impolite_match) {
                return $impolite_match['reply'];
            }
        }
        
        // Nếu không tìm thấy phản hồi, trả về mặc định
        $default_responses = [
            "Xin lỗi, tôi không hiểu câu hỏi của bạn.",
            "Tôi chưa được học câu trả lời cho câu hỏi này.",
            "Bạn có thể hỏi điều gì khác không?",
            "Tôi là chatbot đơn giản, vấn đề này hơi phức tạp với tôi.",
            "Hãy thử hỏi tôi điều gì đó khác nhé!"
        ];
        
        return $default_responses[array_rand($default_responses)];
    } catch (PDOException $e) {
        // Nếu có lỗi database, trả về thông báo lỗi
        return "Xin lỗi, có lỗi xảy ra khi tìm kiếm câu trả lời. Vui lòng thử lại sau.";
    }
}
?> 