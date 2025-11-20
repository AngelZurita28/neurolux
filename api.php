<?php
header('Content-Type: application/json');
require 'db.php';

// Acción solicitada por el frontend
$action = $_GET['action'] ?? '';

// Directorio para subir imágenes
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

switch ($action) {
    case 'register':
        handleRegister($pdo, $uploadDir);
        break;
    case 'login':
        handleLogin($pdo);
        break;
    case 'create_post':
        handleCreatePost($pdo, $uploadDir);
        break;
    case 'get_posts':
        handleGetPosts($pdo);
        break;
    case 'add_comment':
        handleAddComment($pdo);
        break;
    case 'get_comments':
        handleGetComments($pdo);
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

// --- FUNCIONES ---

function handleRegister($pdo, $uploadDir)
{
    $nombre_real = $_POST['nombre_real'] ?? '';
    $nombre_perfil = $_POST['nombre_perfil'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$email || !$password || !$nombre_perfil) {
        echo json_encode(['error' => 'Faltan datos obligatorios']);
        return;
    }

    // Manejo de foto de perfil
    $ruta_foto = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $filepath)) {
            $ruta_foto = $filepath;
        }
    } else {
        // Avatar por defecto
        $ruta_foto = 'https://ui-avatars.com/api/?name=' . urlencode($nombre_real) . '&background=random';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (nombre_real, nombre_perfil, email, password, foto_perfil) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre_real, $nombre_perfil, $email, $hash, $ruta_foto]);

        // Devolver el usuario creado para auto-login
        $userId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $userId,
                'nombre_perfil' => $nombre_perfil,
                'foto_perfil' => $ruta_foto
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'El correo o nombre de usuario ya existe.']);
    }
}

function handleLogin($pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']); // No enviar contraseña
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['error' => 'Credenciales incorrectas']);
    }
}

function handleCreatePost($pdo, $uploadDir)
{
    $usuario_id = $_POST['usuario_id'] ?? 0;
    $titulo = $_POST['titulo'] ?? '';
    $texto = $_POST['texto'] ?? '';

    if (!$usuario_id || !$titulo || !$texto) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Insertar Post
        $stmt = $pdo->prepare("INSERT INTO posts (usuario_id, titulo, texto) VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $titulo, $texto]);
        $post_id = $pdo->lastInsertId();

        // 2. Manejar Imágenes (Múltiples)
        if (!empty($_FILES['imagenes']['name'][0])) {
            $stmtImg = $pdo->prepare("INSERT INTO post_images (post_id, ruta_imagen) VALUES (?, ?)");

            foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['imagenes']['name'][$key], PATHINFO_EXTENSION);
                    $filename = 'post_' . uniqid() . '.' . $ext;
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $stmtImg->execute([$post_id, $filepath]);
                    }
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Error al crear el post: ' . $e->getMessage()]);
    }
}

function handleGetPosts($pdo)
{
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Consulta compleja: Obtener posts + Info Usuario + Primera imagen (o todas) + Conteo Likes
    $sql = "SELECT 
                p.id, p.titulo, p.texto, p.fecha_creacion,
                u.nombre_perfil, u.foto_perfil,
                (SELECT COUNT(*) FROM reactions WHERE post_id = p.id) as likes,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as num_comments
            FROM posts p
            JOIN users u ON p.usuario_id = u.id
            ORDER BY p.fecha_creacion DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    // Obtener imágenes para cada post
    foreach ($posts as &$post) {
        $stmtImg = $pdo->prepare("SELECT ruta_imagen FROM post_images WHERE post_id = ?");
        $stmtImg->execute([$post['id']]);
        $post['images'] = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
    }

    echo json_encode(['posts' => $posts]);
}

function handleAddComment($pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $usuario_id = $data['usuario_id'] ?? 0;
    $post_id = $data['post_id'] ?? 0;
    $texto = $data['texto'] ?? '';

    if (!$usuario_id || !$post_id || !$texto) {
        echo json_encode(['error' => 'Faltan datos']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, usuario_id, texto) VALUES (?, ?, ?)");
    if ($stmt->execute([$post_id, $usuario_id, $texto])) {
        // Devolver el comentario insertado para mostrarlo al instante
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al comentar']);
    }
}

function handleGetComments($pdo)
{
    $post_id = $_GET['post_id'] ?? 0;

    $sql = "SELECT c.texto, c.fecha_creacion, u.nombre_perfil, u.foto_perfil
            FROM comments c
            JOIN users u ON c.usuario_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.fecha_creacion ASC"; // Comentarios antiguos primero

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    echo json_encode(['comments' => $stmt->fetchAll()]);
}
