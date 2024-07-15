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

// Updated SQL to fetch groups and subgroups
$sql = "SELECT SuperGroups.SuperPlotGroup_ID, SuperNames.Name AS SuperGroupName, PlotGroups.ID, PlotGroups.Name AS PlotGroupName
        FROM SuperGroups
        INNER JOIN PlotGroups ON SuperGroups.PlotGroup_ID = PlotGroups.ID
        INNER JOIN PlotGroups AS SuperNames ON SuperGroups.SuperPlotGroup_ID = SuperNames.ID
        ORDER BY SuperGroups.SuperPlotGroup_ID, PlotGroups.Name;";

$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Apply ucfirst to capitalize the first letter of SuperGroupName
        $superGroupName = ucfirst($row['SuperGroupName']);
        $plotGroupEntry = ['id' => $row['ID'], 'name' => $row['PlotGroupName']];

        // Create super group array if it does not exist
        if (!array_key_exists($superGroupName, $data)) {
            $data[$superGroupName] = [];
        }

        // Append the plot group entry to the corresponding super group
        $data[$superGroupName][] = $plotGroupEntry;
    }
    // Convert the data array to JSON and print it
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'No results found']);
}

$conn->close();
?>
