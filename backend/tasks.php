<?php
require_once 'config.php';
$allowedOrigins = explode(',', getenv('ALLOWED_ORIGIN') ?: 'http://localhost:5173');

function handleRequest()
{
    global $allowedOrigins;

    $method = $_SERVER['REQUEST_METHOD'];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Check if the origin is in the allowed origins
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("HTTP/1.1 403 Forbidden");
        exit; // Exit if the origin is not allowed
    }

    header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");


    // Check for OPTIONS request and respond with 200 OK
    if ($method === 'OPTIONS') {
        header("HTTP/1.1 200 OK");
        exit; // Exit after sending headers
    }

    switch ($method) {
        case 'POST':
            createTask();
            break;
        case 'GET':
            readAllTask();
            break;
        case 'PUT':
            updateTask();
            break;
        case 'DELETE':
            deleteTask();
            break;

        default:
            // Send a 405 Method Not Allowed response if the method is not supported
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(["error" => "Method Not Allowed"]);
            break;
    }
}

function createTask()
{
    global $connect;

    $input = json_decode(file_get_contents("php://input"), true);

    // Use null coalescing operator to handle undefined keys
    $title = $input['title'] ?? null;
    $description = $input['description'] ?? null;

    // Check if title and description are set before proceeding
    if ($title === null || $description === null) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Title and description are required']);
        return; // Exit the function early
    }
    $status = 'pending';

    $sql = 'INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)';

    try {
        $stmt = $connect->prepare($sql);
        $stmt->execute([$title, $description, $status]);

        $taskId = $connect->lastInsertId();
        echo json_encode(['message' => 'Task created successfully', 'id' => $taskId]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function readAllTask()
{
    global $connect;
    $sql = 'SELECT * FROM tasks';

    try {
        $stmt = $connect->query($sql);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($tasks);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateTask()
{
    global $connect;
    $input = json_decode(file_get_contents("php://input"), true);

    $id = $input['id'] ?? null;
    $title = $input['title'] ?? null;
    $description = $input['description'] ?? null;
    $status = $input['status'] ?? null;

    if ($id === null || $title === null || $description === null || $status === null) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'All fields (title, description, status) are required']);
        return;
    }

    // Check if the task exists
    $checkSql = 'SELECT COUNT(*) FROM tasks WHERE id = ?';
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->execute([$id]);
    if ($checkStmt->fetchColumn() == 0) {
        echo json_encode(['error' => 'Task not found']);
        return;
    }

    $sql = 'UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ?';

    try {
        $stmt = $connect->prepare($sql);
        $stmt->execute([$title, $description, $status, $id]);
        echo json_encode(['message' => 'Task updated successfully']);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['error' => 'Database error occurred.']);
    }
}

function deleteTask()
{
    global $connect;

    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id'] ?? null;

    if ($id === null) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Task ID is required']);
        return;
    }

    $sql = 'DELETE FROM tasks WHERE id = ?';

    try {
        $stmt = $connect->prepare($sql);
        $stmt->execute([$id]);

        echo json_encode(['message' => 'Task deleted succesfully']);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

handleRequest();
