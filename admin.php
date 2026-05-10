<?php
$user = 'u82196';
$pass = '4736526';
$db_name = 'u82196';
$host = 'localhost';

//HTTP-авторизация
$admin_login = 'admin';
$admin_pass_hash = '$2y$10$vN9p.M/XJsqYVpX.Lq7uG.p9GfWjQeK6fKzL6H2G4B5X1Y2Z3A4B5';

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $admin_login || 
    !password_verify($_SERVER['PHP_AUTH_PW'], $admin_pass_hash)) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Доступ запрещен.');
}

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    //ОБРАБОТКА УДАЛЕНИЯ
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM application WHERE id = ?")->execute([$id]);
        header('Location: admin.php');
        exit();
    }

    //ОБРАБОТКА РЕДАКТИРОВАНИЯ
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $stmt = $db->prepare("UPDATE application SET name=?, email=?, bio=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['bio'], $id]);
        header('Location: admin.php');
        exit();
    }

    //Сбор статистики по языкам
    $sql_stat = "SELECT l.language_id, COUNT(al.application_id) as count 
                 FROM application_languages al 
                 RIGHT JOIN (SELECT DISTINCT language_id FROM application_languages) l ON al.language_id = l.language_id 
                 GROUP BY l.language_id";
    $stats = $db->query($sql_stat)->fetchAll();

    //Получение всех пользователей
    $users = $db->query("SELECT * FROM application")->fetchAll();

} catch (PDOException $e) {
    die('Ошибка: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        .stat-card { background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: inline-block; margin-right: 10px; }
        .btn-del { color: red; text-decoration: none; font-weight: bold; }
        .edit-form input, .edit-form textarea { width: 90%; }
    </style>
</head>
<body>

    <h1>Панель администратора</h1>

    <h2>Статистика по языкам</h2>
    <div>
        <?php foreach ($stats as $s): ?>
            <div class="stat-card">
                <strong>ID Языка:</strong> <?php echo htmlspecialchars($s['language_id']); ?><br>
                <strong>Любителей:</strong> <?php echo $s['count']; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Список пользователей</h2>
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
            <form method="POST" class="edit-form">
                <td><?php echo $u['id']; ?><input type="hidden" name="edit_id" value="<?php echo $u['id']; ?>"></td>
                <td><input type="text" name="name" value="<?php echo htmlspecialchars($u['name']); ?>"></td>
                <td><input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>"></td>
                <td><textarea name="bio"><?php echo htmlspecialchars($u['bio']); ?></textarea></td>
                <td>
                    <button type="submit">Сохранить</button>
                    <a href="admin.php?delete=<?php echo $u['id']; ?>" class="btn-del" onclick="return confirm('Удалить?')">Удалить</a>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>

    <p><a href="index.php">Вернуться к форме</a></p>

</body>
</html>
