<?php
// Csatlakozás az adatbázishoz
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gymone";

// Az id, amit keresel
$ticketbuyerid = 1340113322; // Ezt az értéket módosítsd a keresett userid-re

// Kapcsolat létrehozása
$conn = new mysqli($servername, $username, $password, $dbname);

// Ellenőrizd a kapcsolatot
if ($conn->connect_error) {
    die("Kapcsolati hiba: " . $conn->connect_error);
}

// SQL lekérdezés
$sql = "SELECT firstname, lastname, email, gender, birthdate, city, street, house_number FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);

// Ellenőrizd, hogy a lekérdezés előkészítése sikerült-e
if (!$stmt) {
    die("Hiba az SQL lekérdezés előkészítése során: " . $conn->error);
}

$stmt->bind_param("i", $ticketbuyerid);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email, $gender, $birthdate, $city, $street, $house_number);

// Ellenőrizd, hogy van-e eredmény
if ($stmt->fetch()) {
    // Az adatokat itt tároljuk
    echo "First Name: " . $firstname . "<br>";
    echo "Last Name: " . $lastname . "<br>";
    echo "Email: " . $email . "<br>";
    echo "Gender: " . $gender . "<br>";
    echo "Birthdate: " . $birthdate . "<br>";
    echo "City: " . $city . "<br>";
    echo "Street: " . $street . "<br>";
    echo "House Number: " . $house_number . "<br>";
} else {
    echo "Nincsenek adatok a megadott userid-hoz.";
}

// Kapcsolat lezárása
$stmt->close();
$conn->close();
?>
