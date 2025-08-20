<?php
// /matchymatchy/api/admin/users.php
declare(strict_types=1);

/**
 * Users API for Matchy Matchy Admin.
 * Actions: list, get, create, update, delete
 */

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* Always return JSON on PHP errors (easier debugging in UI) */
set_error_handler(function($errno, $errstr, $file, $line){
    http_response_code(500);
    echo json_encode(['error' => 'PHP error', 'detail' => "$errstr @ $file:$line"]);
    exit;
});
set_exception_handler(function(Throwable $e){
    http_response_code(500);
    echo json_encode(['error' => 'Unhandled exception', 'detail' => $e->getMessage()]);
    exit;
});

/* ========= DB config ========= */
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'matchy_matchy';

/* ========= Helpers ========= */
function db(): mysqli {
    static $conn;
    if (!$conn) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
function send(int $code, array $payload): never {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function body(): array {
    $raw = file_get_contents('php://input') ?: '';
    $j = json_decode($raw, true);
    if (is_array($j)) return $j;
    return $_POST ?: [];
}
function method(string $m): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $m) {
        send(405, ['error' => 'Method Not Allowed']);
    }
}
function ref_values(array &$a): array { $r=[]; foreach ($a as $k=>&$v) $r[$k]=&$v; return $r; }

/* ========= Validation ========= */
const ROLES  = ['Administrator','Customer'];          // <- only 2 roles now
const STATUS = ['Active','Pending','Suspended'];

function vstr(?string $s, int $max=255): string { return mb_substr(trim((string)$s), 0, $max); }
function vemail(?string $e): string {
    $e = vstr($e, 255);
    if (!filter_var($e, FILTER_VALIDATE_EMAIL)) send(422, ['error'=>'Invalid email']);
    return $e;
}
function vrole(?string $r): string {
    $r = vstr($r, 20);
    if (!in_array($r, ROLES, true)) send(422, ['error'=>'Invalid role']);
    return $r;
}
function vstatus(?string $s): string {
    $s = vstr($s, 20);
    if (!in_array($s, STATUS, true)) send(422, ['error'=>'Invalid status']);
    return $s;
}
/* Password: require >= 8 chars when provided */
function vpass_required(?string $p): string {
    $p = (string)$p;
    if (mb_strlen($p) < 8) send(422, ['error'=>'Password must be at least 8 characters']);
    return $p;
}
function vpass_optional(?string $p): ?string {
    $p = trim((string)$p);
    if ($p === '') return null;
    if (mb_strlen($p) < 8) send(422, ['error'=>'Password must be at least 8 characters']);
    return $p;
}

/* Normalize username to ALWAYS store with exactly one leading '@' */
function normalize_username(?string $u): ?string {
    $u = vstr($u ?? '', 50);
    if ($u === '') return null;
    return '@' . ltrim($u, '@');
}

/* ========= DTO ========= */
function row_to_dto(array $r): array {
    $name = trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? ''));
    $username = $r['username'] ?? null;   // stored WITH @
    $handle   = $username ?: null;        // same value; do not add another '@'
    return [
        'id'           => (int)$r['id'],
        'first_name'   => $r['first_name'],
        'last_name'    => $r['last_name'],
        'name'         => $name,
        'username'     => $username,
        'handle'       => $handle,
        'email'        => $r['email'],
        'phone'        => $r['phone'],
        'avatar'       => $r['avatar'],
        'role'         => $r['role'],
        'status'       => $r['status'],
        'notes'        => $r['notes'],
        'provider'     => $r['provider'],
        'joined'       => $r['created_at'],
        'has_password' => !empty($r['password'] ?? null),  // <- safe flag
    ];
}

/* ========= Schema bootstrap ========= */
function ensure_schema(mysqli $db): void {
    $db->query("CREATE TABLE IF NOT EXISTS users (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        first_name        VARCHAR(50)  NOT NULL,
        last_name         VARCHAR(50)  NOT NULL,
        email             VARCHAR(255) NOT NULL UNIQUE,
        password          VARCHAR(255) NULL,
        avatar            VARCHAR(255) NULL,
        provider          ENUM('local','google') NOT NULL DEFAULT 'local',
        google_id         VARCHAR(64)  NULL UNIQUE,
        email_verified_at DATETIME NULL,
        created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Discover existing columns
    $cols = [];
    $res = $db->query("SHOW COLUMNS FROM users");
    while ($c = $res->fetch_assoc()) $cols[$c['Field']] = $c;

    // username
    if (!isset($cols['username'])) {
        $db->query("ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL UNIQUE");
    }

    // role -> ensure only two values in ENUM
    if (!isset($cols['role'])) {
        $db->query("ALTER TABLE users ADD COLUMN role ENUM('Administrator','Customer') NOT NULL DEFAULT 'Customer'");
    } else {
        $type = $cols['role']['Type'] ?? '';
        if (strpos($type, "'Guest'") !== false) {
            // migrate enum to 2 values (will fail if rows still have 'Guest')
            try {
                $db->query("ALTER TABLE users MODIFY role ENUM('Administrator','Customer') NOT NULL DEFAULT 'Customer'");
            } catch (Throwable $e) {
                // If migration fails due to existing 'Guest' values, keep as-is.
            }
        }
    }

    // status
    if (!isset($cols['status'])) {
        $db->query("ALTER TABLE users ADD COLUMN status ENUM('Active','Pending','Suspended') NOT NULL DEFAULT 'Active'");
    }

    // phone / notes
    if (!isset($cols['phone']))  $db->query("ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL");
    if (!isset($cols['notes']))  $db->query("ALTER TABLE users ADD COLUMN notes TEXT NULL");

    // indexes
    try { $db->query("CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at)"); } catch (Throwable $e) {}
    try { $db->query("CREATE INDEX idx_users_name_email ON users(username, email)"); } catch (Throwable $e) {}
}

/* ========= Actions ========= */
$conn = db();
ensure_schema($conn);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        method('GET');

        $q       = isset($_GET['q']) ? vstr($_GET['q'], 100) : '';
        $role    = ($_GET['role']   ?? '') !== '' ? vrole($_GET['role'])   : null;
        $status  = ($_GET['status'] ?? '') !== '' ? vstatus($_GET['status']) : null;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $size    = max(1, min(50, (int)($_GET['page_size'] ?? 8)));
        $offset  = ($page - 1) * $size;

        $where = [];
        $params = [];
        $types = '';

        if ($q !== '') {
            $where[] = "(CONCAT(first_name,' ',last_name) LIKE ? OR email LIKE ? OR username LIKE ?)";
            $like = "%{$q}%";
            $params[]=$like; $params[]=$like; $params[]=$like;
            $types  .= 'sss';
        }
        if ($role)   { $where[] = "role=?";   $params[]=$role;   $types.='s'; }
        if ($status) { $where[] = "status=?"; $params[]=$status; $types.='s'; }

        $sqlWhere = $where ? 'WHERE '.implode(' AND ', $where) : '';

        // count
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM users $sqlWhere");
        if ($types) $stmt->bind_param($types, ...ref_values($params));
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $pages = max(1, (int)ceil($total / $size));
        if ($page > $pages) { $page = $pages; $offset = ($page - 1) * $size; }

        // data (include password to compute has_password; do NOT return it)
        $sql = "SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at, password
                FROM users $sqlWhere
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $types2 = $types.'ii';
        $params2 = $params;
        $params2[] = $size; $params2[] = $offset;
        $stmt->bind_param($types2, ...ref_values($params2));
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = row_to_dto($r);
        $stmt->close();

        send(200, ['data'=>$data, 'meta'=>[
            'page'=>$page, 'page_size'=>$size, 'total'=>$total, 'pages'=>$pages
        ]]);
        break;

    case 'get':
        method('GET');
        $id = max(0, (int)($_GET['id'] ?? 0));
        if ($id <= 0) send(422, ['error'=>'Invalid id']);

        $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at, password
                                FROM users WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) send(404, ['error'=>'Not found']);
        send(200, ['data'=>row_to_dto($row)]);
        break;

    case 'create':
        method('POST');
        $in = body();

        $first    = vstr($in['first_name'] ?? '', 50);
        $last     = vstr($in['last_name']  ?? '', 50);
        $email    = vemail($in['email']    ?? '');
        $username = normalize_username($in['username'] ?? null); // store WITH @
        $phone    = vstr($in['phone']  ?? '', 30) ?: null;
        $avatar   = vstr($in['avatar'] ?? '', 255) ?: null;
        $notes    = vstr($in['notes']  ?? '', 10000) ?: null;
        $role     = vrole($in['role']   ?? 'Customer');
        $status   = vstatus($in['status'] ?? 'Active');

        // password required on create
        $password = vpass_required($in['password'] ?? null);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($first === '' || $last === '') send(422, ['error'=>'First and last name are required']);

        // uniqueness
        if ($username) {
            $s = $conn->prepare("SELECT 1 FROM users WHERE username=? LIMIT 1");
            $s->bind_param('s', $username);
            $s->execute();
            if ($s->get_result()->fetch_row()) { $s->close(); send(409, ['error'=>'Username already exists']); }
            $s->close();
        }
        $s = $conn->prepare("SELECT 1 FROM users WHERE email=? LIMIT 1");
        $s->bind_param('s', $email);
        $s->execute();
        if ($s->get_result()->fetch_row()) { $s->close(); send(409, ['error'=>'Email already exists']); }
        $s->close();

        $sql = "INSERT INTO users (first_name,last_name,username,email,password,avatar,provider,google_id,email_verified_at,phone,notes,role,status)
                VALUES (?,?,?,?,?,?,'local',NULL,NULL,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        // placeholders: first,last,username,email,password,avatar,phone,notes,role,status  (10)
        $stmt->bind_param('ssssssssss', $first,$last,$username,$email,$hash,$avatar,$phone,$notes,$role,$status);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at, password FROM users WHERE id=?");
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        send(201, ['data'=>row_to_dto($row)]);
        break;

    case 'update':
        method('POST');
        $in = body();

        $id       = (int)($in['id'] ?? 0);
        if ($id <= 0) send(422, ['error'=>'Invalid id']);

        $first    = vstr($in['first_name'] ?? '', 50);
        $last     = vstr($in['last_name']  ?? '', 50);
        $email    = vemail($in['email']    ?? '');
        $username = normalize_username($in['username'] ?? null);
        $phone    = vstr($in['phone']  ?? '', 30) ?: null;
        $avatar   = vstr($in['avatar'] ?? '', 255) ?: null;
        $notes    = vstr($in['notes']  ?? '', 10000) ?: null;
        $role     = vrole($in['role']   ?? 'Customer');
        $status   = vstatus($in['status'] ?? 'Active');
        $newPass  = vpass_optional($in['password'] ?? null);

        $s = $conn->prepare("SELECT 1 FROM users WHERE id=?");
        $s->bind_param('i', $id);
        $s->execute();
        if (!$s->get_result()->fetch_row()) { $s->close(); send(404, ['error'=>'Not found']); }
        $s->close();

        if ($username) {
            $s = $conn->prepare("SELECT 1 FROM users WHERE username=? AND id<>? LIMIT 1");
            $s->bind_param('si', $username, $id);
            $s->execute();
            if ($s->get_result()->fetch_row()) { $s->close(); send(409, ['error'=>'Username already exists']); }
            $s->close();
        }
        $s = $conn->prepare("SELECT 1 FROM users WHERE email=? AND id<>? LIMIT 1");
        $s->bind_param('si', $email, $id);
        $s->execute();
        if ($s->get_result()->fetch_row()) { $s->close(); send(409, ['error'=>'Email already exists']); }
        $s->close();

        $sql = "UPDATE users SET first_name=?, last_name=?, username=?, email=?, phone=?, avatar=?, notes=?, role=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssssi', $first,$last,$username,$email,$phone,$avatar,$notes,$role,$status,$id);
        $stmt->execute();
        $stmt->close();

        if ($newPass !== null) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $s2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $s2->bind_param('si', $hash, $id);
            $s2->execute();
            $s2->close();
        }

        $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at, password FROM users WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        send(200, ['data'=>row_to_dto($row)]);
        break;

    case 'delete':
        method('POST');
        $in = body();
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) send(422, ['error'=>'Invalid id']);

        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) send(404, ['error'=>'Not found']);
        send(200, ['deleted'=>true, 'id'=>$id]);
        break;

    default:
        send(400, ['error'=>'Unknown or missing action']);
}
