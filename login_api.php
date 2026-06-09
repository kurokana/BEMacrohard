<?php

header('Content-Type: application/json');

$conn = new mysqli(
    "localhost",
    "vccuser",
    "12345",
    "macrohard_db"
);

if ($conn->connect_error) {

    die(json_encode([
        "success" => false
    ]));
}

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$username = $data['username'];
$password = $data['password'];

$sql =
"SELECT * FROM users
WHERE username=?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

if ($user &&
    password_verify(
        $password,
        $user['password']
    )) {

    echo json_encode([

        "success" => true,

        "user" => $user
    ]);

} else {

    echo json_encode([

        "success" => false
    ]);
}
?>
