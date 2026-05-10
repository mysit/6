<?php
$user = 'u82196';
$pass = '4736526';
$db_name = 'u82196';
$host = 'localhost';

$admin_login = 'admin';
$admin_pass  = '123';

if (empty($_SERVER['PHP_AUTH_USER'])) {
    if (preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '', $matches)) {
        list($user_msg, $pw_msg) = explode(':', base64_decode($matches[1]));
        $_SERVER['PHP_AUTH_USER'] = $user_msg;
        $_SERVER['PHP_AUTH_PW'] = $pw_msg;
    }
}

// Проверка логина и пароля
if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $admin_login || 
    $_SERVER['PHP_AUTH_PW']   !== $admin_pass) {
    
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Доступ запрещен. Введите корректные данные.');
}

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    //УДАЛЕНИЕ ПОЛЬЗОВАТЕЛЯ
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        // Сначала удаляем связи с языками
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        // Затем самого пользователя
        $db->prepare("DELETE FROM application WHERE id = ?")->execute([$id]);
        header('Location: admin.php');
        exit();
    }

    //РЕДАКТИРОВАНИЕ ДАННЫХ 
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $stmt = $db->prepare("UPDATE application SET name=?, email=?, bio=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['bio'], $id]);
        header('Location: admin.php');
        exit();
    }

    //СТАТИСТИКА ПО ЯЗЫКАМ
    // Считаем количество упоминаний каждого языка в таблице связей
    $stats = $db->query("
        SELECT language_id, COUNT(*) as count 
        FROM application_languages 
        GROUP BY language_id
    ")->fetchAll();

    //СПИСОК ВСЕХ ПОЛЬЗОВАТЕЛЕЙ
    $users = $db->query("SELECT * FROM application ORDER BY id DESC")->fetchAll();

} catch (PDOException $e) {
    die('Ошибка базы данных: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка</title>
    <style>
        body { font-family: sans-serif; padding: 30px; }
        .container { max-width: 1000px; margin: auto; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #aaaaaa; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .stats { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-item { background: #e9ecef; padding: 10px 15px; border-radius: 4px; }
        input, textarea { width: 100%; box-sizing: border-box; padding: 5px; }
        .btn-save { background: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
        .btn-del { color: #dc3545; text-decoration: none; margin-left: 10px; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="container">
    <h1>Панель администратора</h1>

    <h3>Статистика по языкам:</h3>
    <div class="stats">
        <?php foreach ($stats as $s): ?>
            <div class="stat-item">
                <strong>ID: <?php echo htmlspecialchars($s['language']); ?></strong> — <?php echo $s['count']; ?> чел.
            </div>
        <?php endforeach; ?>
    </div>

    <h3>Список пользователей:</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Email</th>
            <th>Биография</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <form method="POST">
                <td><?php echo $u['id']; ?><input type="hidden" name="edit_id" value="<?php echo $u['id']; ?>"></td>
                <td><input type="text" name="name" value="<?php echo htmlspecialchars($u['name']); ?>"></td>
                <td><input type="text" name="email" value="<?php echo htmlspecialchars($u['email']); ?>"></td>
                <td><textarea name="bio"><?php echo htmlspecialchars($u['bio']); ?></textarea></td>
                <td>
                    <button type="submit" class="btn-save">OK</button>
                    <a href="admin.php?delete=<?php echo $u['id']; ?>" class="btn-del" onclick="return confirm('Удалить пользователя?')">Удалить</a>
               </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <br>
    <a href="index.php">← Назад к форме</a>
</div>
