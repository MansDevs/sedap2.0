<?php
require 'pages/config/db.php';
$u = $pdo->query("SELECT id, name, username, email, role, status FROM users");
while($r = $u->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
