<?php
echo "<h1>LAMP Docker — Etape 2 (3 conteneurs)</h1>";
echo "<p>PHP-FPM Version : " . phpversion() . "</p>";
echo "<hr>";

$host     = "db";
$username = "lamp_user";
$password = "Lamp@2026#Secure";
$dbname   = "lamp_test";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("<p style='color:red'>Connexion MySQL échouée : "
        . $conn->connect_error . "</p>");
}

echo "<p style='color:green'> Connexion MySQL réussie !</p>";
echo "<p>Serveur MySQL : " . $conn->server_info . "</p>";

// Crée la table si elle n'existe pas
$conn->query("CREATE TABLE IF NOT EXISTS todo_list (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    content VARCHAR(255)
)");

// Insère des données si vide
$result = $conn->query("SELECT COUNT(*) as nb FROM todo_list");
$row = $result->fetch_assoc();
if ($row['nb'] == 0) {
    $conn->query("INSERT INTO todo_list (content) VALUES
        ('Docker Etape 2 — 3 conteneurs'),
        ('apache  : httpd:2.4 + mod_proxy_fcgi'),
        ('phpfpm  : php:8.3-fpm port 9000'),
        ('db      : mysql:8.0 port 3306'),
        ('Réseau  : lamp_network (noms de services)')
    ");
}

// Affiche les données
$result = $conn->query("SELECT item_id, content FROM todo_list");
echo "<h2>TODO LIST</h2><ul>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<li>[" . $row['item_id'] . "] "
             . htmlspecialchars($row['content']) . "</li>";
    }
} else {
    echo "<li>Aucune tâche</li>";
}
echo "</ul>";
$conn->close();
?>

