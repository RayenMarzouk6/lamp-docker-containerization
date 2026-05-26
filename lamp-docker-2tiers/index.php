<?php
$servername = "db";        // ← nom du container MySQL
$username = "lamp_user";
$password = "Lamp@2026#Secure";
$dbname = "lamp_test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Crée la table si elle n'existe pas encore
$conn->query("CREATE TABLE IF NOT EXISTS todo_list (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    content VARCHAR(255)
)");

// Insère des données si la table est vide
$result = $conn->query("SELECT COUNT(*) as nb FROM todo_list");
$row = $result->fetch_assoc();
if ($row['nb'] == 0) {
    $conn->query("INSERT INTO todo_list (content) VALUES
        ('Docker Etape 1 | 2 conteneur'),
        ('Conteneur web : PHP + Apache'),
        ('Conteneur db  : MySQL 8.0'),
        ('Communication par nom de service : db')
    ");
}

// Affiche les données
$result = $conn->query("SELECT item_id, content FROM todo_list");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Todo List - Docker 2-Tiers</title>
</head>
<body>
    <h1>Todo List (Docker 2-Tiers)</h1>
    <p style="color:green">Connecté à MySQL : <?php echo $servername; ?></p>
    <ul>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row["content"]) . "</li>";
        }
    } else {
        echo "<li>Aucune tâche trouvée</li>";
    }
    ?>
    </ul>
</body>
</html>
<?php $conn->close(); ?>
