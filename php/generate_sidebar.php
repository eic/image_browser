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

$superPlotGroup_ID = isset($_GET['superPlotGroup_ID']) ? (int) $_GET['superPlotGroup_ID'] : 0;
if ($superPlotGroup_ID == 0) {
    die("Invalid superPlotGroup_ID provided.");
}

$stmt = $conn->prepare("
    SELECT 
        SuperGroups.SuperPlotGroup_ID, 
        SuperNames.Name AS SuperGroupName, 
        PlotGroups.ID AS PlotGroupID, 
        PlotGroups.Name AS PlotGroupName, 
        SubPlotGroups.ID AS SubPlotGroupID, 
        SubPlotGroups.Name AS SubPlotGroupName
    FROM 
        SuperGroups
    INNER JOIN 
        PlotGroups ON SuperGroups.PlotGroup_ID = PlotGroups.ID
    INNER JOIN 
        PlotGroups AS SuperNames ON SuperGroups.SuperPlotGroup_ID = SuperNames.ID 
    INNER JOIN 
        SuperGroups AS SubGroups ON SubGroups.SuperPlotGroup_ID = PlotGroups.ID
    INNER JOIN 
        PlotGroups AS SubPlotGroups ON SubGroups.PlotGroup_ID = SubPlotGroups.ID
    WHERE 
        SuperGroups.SuperPlotGroup_ID = ?
");

$stmt->bind_param("i", $superPlotGroup_ID);
$stmt->execute();
$result = $stmt->get_result();


$plotGroups = [];
while ($row = $result->fetch_assoc()) {
    $plotGroupName = $row['PlotGroupName'];
    $subPlotGroupName = $row['SubPlotGroupName'];

    if (!isset($plotGroups[$plotGroupName])) {
        $plotGroups[$plotGroupName] = [];
    }

    $plotGroups[$plotGroupName][] = $subPlotGroupName;
}

// Second query to fetch PlotType_IDs and names
$plotTypeStmt = $conn->prepare("
    SELECT Plot_Types.ID, Plot_Types.Name 
    FROM Plot_Types 
    INNER JOIN PlotType_Groupings ON Plot_Types.ID = PlotType_Groupings.PlotType_ID 
    WHERE PlotType_Groupings.PlotGroup_ID = ?
");
$plotTypeStmt->bind_param('i', $superPlotGroup_ID);
$plotTypeStmt->execute();
$plotTypeResult = $plotTypeStmt->get_result();

$plotTypes = [];
while ($typeRow = $plotTypeResult->fetch_assoc()) {
    $plotTypes[] = $typeRow['Name'];
}

$data = [];
foreach ($plotGroups as $key => $value) {
    $data[$key] = $value;
    if ($key == 'Campaign') {
        $data['PlotTypes'] = $plotTypes;
    }
}

header('Content-Type: application/json');
echo json_encode($data);

$result->free();
$plotTypeResult->free();
$stmt->close();
$plotTypeStmt->close();
?>