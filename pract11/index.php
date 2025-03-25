<?php
// Подключение к базе данных
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'pract11';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Функция для вывода таблицы
function displayTable($table_name, $conn) {
    echo "<h3>Таблица: $table_name</h3>";

    // Получение данных из таблицы
    $sql = "SELECT * FROM $table_name";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";

        // Вывод заголовков столбцов
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "<th>Действия</th>"; // Колонка для кнопок редактирования и удаления
        echo "</tr>";

        // Вывод строк данных
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }

            // Кнопки редактирования и удаления
            echo "<td>";
            echo "<a href='?edit_table=$table_name&id=" . $row['id'] . "'>Редактировать</a> | ";
            echo "<a href='?delete_table=$table_name&id=" . $row['id'] . "' onclick='return confirm(\"Вы уверены?\")'>Удалить</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "Таблица пустая.<br><br>";
    }
}

// Обработка добавления записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
    $table_name = $_POST['add_table'];
    $columns = [];
    $placeholders = [];
    $values = [];

    // Собираем данные из POST-запроса
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'column_') === 0) {
            $column_name = str_replace('column_', '', $key);
            $columns[] = $column_name;
            $placeholders[] = '?'; // Для подготовленных выражений
            $values[] = $value;   // Значения для подстановки
        }
    }

    // Формируем SQL-запрос
    $columns_sql = implode(', ', $columns);
    $placeholders_sql = implode(', ', $placeholders);
    $sql = "INSERT INTO $table_name ($columns_sql) VALUES ($placeholders_sql)";

    // Используем подготовленное выражение
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Привязываем значения к параметрам
        $types = str_repeat('s', count($values)); // Все значения считаются строками ('s')
        $stmt->bind_param($types, ...$values);

        // Выполняем запрос
        if ($stmt->execute()) {
            // Перенаправляем пользователя после успешного добавления
            header("Location: index.php?status=success");
            exit();
        } else {
            echo "Ошибка выполнения запроса: " . $stmt->error;
        }
    } else {
        echo "Ошибка подготовки запроса: " . $conn->error;
    }
}

// Обработка удаления записи
if (isset($_GET['delete_table']) && isset($_GET['id'])) {
    $table_name = $_GET['delete_table'];
    $id = $_GET['id'];

    $sql = "DELETE FROM $table_name WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        echo "Запись успешно удалена!";
    } else {
        echo "Ошибка: " . $conn->error;
    }
}

// Обработка редактирования записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_table'])) {
    $table_name = $_POST['edit_table'];
    $id = $_POST['id'];
    $updates = [];

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'column_') === 0) {
            $column = str_replace('column_', '', $key);
            $updates[] = "$column = '" . $conn->real_escape_string($value) . "'";
        }
    }

    $updates_sql = implode(', ', $updates);

    $sql = "UPDATE $table_name SET $updates_sql WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        echo "Запись успешно обновлена!";
    } else {
        echo "Ошибка: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Гостиница</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
        }
        h1{
            width: 100%;
            padding: 0%;
            margin: 0;
            height: 100px;
            text-align: center;
            padding-top: 50px;
            background-color: #007bff;
            color: white;
        }
        h3 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        form {
            margin-bottom: 20px;
            padding: 15px;
            width: 500px;
            border: 2px solid #007bff;
            border-radius: 10px;
            display: flex;
            text-align: center;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        input[type="text"], input[type="email"] {
            padding: 8px;
            margin-right: 10px;
            width: 200px;
        }
        button {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        a {
            text-decoration: none;
            color: #007bff;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Содержимое базы данных 'hotel_db'</h1>

    <?php
    // Получение списка всех таблиц
    $tables_result = $conn->query("SHOW TABLES");
    while ($table_row = $tables_result->fetch_row()) {
        $table_name = $table_row[0];

        // Вывод таблицы
        displayTable($table_name, $conn);

        // Форма для добавления записи
        echo "<h4>Добавить запись в таблицу '$table_name'</h4>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='add_table' value='$table_name'>";

        // Получение структуры таблицы
        $columns_result = $conn->query("DESCRIBE $table_name");
        while ($column = $columns_result->fetch_assoc()) {
            $column_name = $column['Field'];
            if ($column_name !== 'id') { // Исключаем поле 'id'
                echo "<label>$column_name:</label>";
                echo "<input type='text' name='column_$column_name'><br>";
            }
        }

        echo "<button type='submit'>Добавить</button>";
        echo "</form>";

        // Форма для редактирования записи
        if (isset($_GET['edit_table']) && $_GET['edit_table'] === $table_name && isset($_GET['id'])) {
            $id = $_GET['id'];
            $sql = "SELECT * FROM $table_name WHERE id = $id";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();

            echo "<h4>Редактировать запись в таблице '$table_name'</h4>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='edit_table' value='$table_name'>";
            echo "<input type='hidden' name='id' value='$id'>";

            foreach ($row as $column_name => $value) {
                if ($column_name !== 'id') {
                    echo "<label>$column_name:</label>";
                    echo "<input type='text' name='column_$column_name' value='$value'><br>";
                }
            }

            echo "<button type='submit'>Сохранить</button>";
            echo "</form>";
        }
    }

    // Функция для вывода таблицы
function displayRequest($title, $result) {
    if ($result->num_rows > 0) {
        echo "<h3>$title</h3>";
        echo "<table border='1' cellpadding='5'>";
        // Вывод заголовков столбцов
        echo "<tr>";
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        // Вывод строк данных
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<p>Нет данных для запроса: $title</p><br>";
    }
}

// Запрос 1: Список номеров с типами и доступностью
$sql1 = "
    SELECT r.room_num, rt.type_name, r.price, r.is_available
    FROM rooms r
    JOIN room_types rt ON r.id = rt.id
";
$result1 = $conn->query($sql1);
displayRequest("Список номеров с типами", $result1);

// Запрос 2: Клиенты, проживающие сегодня
$sql2 = "
    SELECT c.surname, c.name, c.phone, b.check_in_date, b.check_out_date
    FROM bookings b
    JOIN clients c ON b.id = c.id
    WHERE CURDATE() BETWEEN b.check_in_date AND b.check_out_date
";
$result2 = $conn->query($sql2);
displayRequest("Клиенты, проживающие сегодня", $result2);

// Запрос 3: Средняя оценка отзывов по каждому типу номера
$sql3 = "
    SELECT rt.type_name, AVG(rv.rating) AS average_rating
    FROM reviews rv
    JOIN bookings b ON rv.client_id = b.client_id
    JOIN rooms r ON b.id = r.id
    JOIN room_types rt ON r.id = rt.id
    GROUP BY rt.type_name
";
$result3 = $conn->query($sql3);
displayRequest("Средняя оценка отзывов по типам номеров", $result3);

// Запрос 4: Общая сумма платежей за последние 30 дней
$sql4 = "
    SELECT SUM(amount) AS total_payments
    FROM payments
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$result4 = $conn->query($sql4);
if ($result4->num_rows > 0) {
    $row = $result4->fetch_assoc();
    echo "<h3>Общая сумма платежей за последние 30 дней</h3>";
    echo "<p>Сумма: " . $row['total_payments'] . "</p><br>";
} else {
    echo "<p>Нет данных для запроса: Общая сумма платежей за последние 30 дней</p><br>";
}

// Запрос 5: Количество обработанных бронирований сотрудниками
$sql5 = "
    SELECT e.surname, e.name, COUNT(b.id) AS bookings_count
    FROM employees e
    LEFT JOIN bookings b ON e.id = b.client_id
    GROUP BY e.surname, e.name
";
$result5 = $conn->query($sql5);
displayRequest("Количество обработанных бронирований сотрудниками", $result5);
    ?>

</body>
</html>