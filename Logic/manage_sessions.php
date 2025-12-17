<?php
session_start();
include "db.php";
header('Content-Type: application/json');

$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page-1)*$perPage;

if($_SERVER['REQUEST_METHOD']=='GET'){
    // Fetch total sessions count
    $totalResult = $conn->query("SELECT COUNT(*) as count FROM games");
    $totalCount = $totalResult->fetch_assoc()['count'];
    $totalPages = ceil($totalCount/$perPage);

    $sql = "SELECT * FROM games ORDER BY start_time DESC LIMIT $offset, $perPage";
    $res = $conn->query($sql);

    $sessions = [];
    while($row = $res->fetch_assoc()){
        $sessions[] = $row;
    }

    echo json_encode(['sessions'=>$sessions,'totalPages'=>$totalPages]);
    exit();
}

if($_SERVER['REQUEST_METHOD']=='POST'){
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if($action==='delete' && $id){
        $stmt = $conn->prepare("DELETE FROM games WHERE id=?");
        $stmt->bind_param("i",$id);
        if($stmt->execute()){
            echo json_encode(['status'=>'ok']);
        } else {
            echo json_encode(['status'=>'error']);
        }
        exit();
    }

    echo json_encode(['status'=>'error']);
}
?>
