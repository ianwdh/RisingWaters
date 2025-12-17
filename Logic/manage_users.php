<?php
session_start();
include "db.php";
header('Content-Type: application/json');

$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page-1)*$perPage;

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Fetch total users
    $totalRes = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalRow = $totalRes->fetch_assoc();
    $totalPages = ceil($totalRow['total']/$perPage);

    $stmt = $conn->prepare("SELECT id, username, usertype FROM users ORDER BY id ASC LIMIT ?,?");
    $stmt->bind_param("ii",$offset,$perPage);
    $stmt->execute();
    $res = $stmt->get_result();
    $users = [];
    while($row = $res->fetch_assoc()) $users[] = $row;

    echo json_encode(['users'=>$users,'totalPages'=>$totalPages]);
    exit();
}

// POST for edit/delete
$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;

if($action==='edit'){
    $username = trim($_POST['username'] ?? '');
    $usertype = trim($_POST['usertype'] ?? '');
    if(!$username || !$usertype){
        echo json_encode(['status'=>'error']); exit();
    }

    // Check if username taken by another
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND id<>?");
    $stmt->bind_param("si",$username,$id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        echo json_encode(['status'=>'taken']); exit();
    }

    $stmt = $conn->prepare("UPDATE users SET username=?, usertype=? WHERE id=?");
    $stmt->bind_param("ssi",$username,$usertype,$id);
    if($stmt->execute()) echo json_encode(['status'=>'ok']);
    else echo json_encode(['status'=>'error']);
    exit();
}

if($action==='delete'){
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i",$id);
    if($stmt->execute()) echo json_encode(['status'=>'ok']);
    else echo json_encode(['status'=>'error']);
    exit();
}

echo json_encode(['status'=>'error']);
