<?php
require_once('./configurations.php');

$servername = $FEconfig['dbhost'];
$username = $FEconfig['dbuser'];
$password = $FEconfig['dbpass'];
$dbname = $FEconfig['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM PlotGroups ORDER BY Type, Name;";
$result = $conn->query($sql);

$data = ['Physics' => [], 'Detector' => [],'Reconstruction' => []];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $entry = ['id' => $row['ID'], 'name' => $row['Name']];
        if ($row['Type'] === 'physics') {
            $data['Physics'][] = $entry;
        } elseif ($row['Type'] === 'detector') {
            $data['Detector'][] = $entry;
        } elseif ($row['Type'] === 'reconstruction') {
            $data['Reconstruction'][] = $entry;
        }
    }
    // Convert the data array to JSON and print it
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'No results found']);
}

$conn->close();
?>
