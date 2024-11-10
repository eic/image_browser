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


// Check if result is empty
$plotGroups = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plotGroupName = $row['PlotGroupName'];
        $subPlotGroupName = $row['SubPlotGroupName'];

        if (!isset($plotGroups[$plotGroupName])) {
            $plotGroups[$plotGroupName] = [];
        }

        $plotGroups[$plotGroupName][] = $subPlotGroupName;
    }
} else {
    // If no result, fetch from PlotType_Groupings where superPlotGroup_ID = PlotGroup_ID
    $groupingStmt = $conn->prepare("
        SELECT PlotType_Groupings.PlotGroup_ID, Plot_Types.Name, Plot_Types.Description, Plot_Types.DisplayName
        FROM PlotType_Groupings
        INNER JOIN Plot_Types ON PlotType_Groupings.PlotType_ID = Plot_Types.ID
        WHERE PlotType_Groupings.PlotGroup_ID = ?
    ");
    $groupingStmt->bind_param("i", $superPlotGroup_ID);
    $groupingStmt->execute();
    $groupingResult = $groupingStmt->get_result();

    while ($groupingRow = $groupingResult->fetch_assoc()) {
        $plotGroupID = $groupingRow['PlotGroup_ID'];
        $plotTypeName = $groupingRow['Name'];
        $plotTypeDescription = $groupingRow['Description'];
        $plotTypeDisplayName = $groupingRow['DisplayName'];

        if (!isset($plotGroups[$plotGroupID])) {
            $plotGroups[$plotGroupID] = [];
        }

        $plotGroups['PlotTypes'][] = ['Name' => $plotTypeName, 'Description' => $plotTypeDescription, 'DisplayName' => $plotTypeDisplayName['DisplayName']];
    }

    $groupingResult->free();
    $groupingStmt->close();
}


// Second query to fetch PlotType_IDs and names
$plotTypeStmt = $conn->prepare("
    SELECT Plot_Types.ID, Plot_Types.Name, Plot_Types.Description, Plot_Types.DisplayName 
    FROM Plot_Types 
    INNER JOIN PlotType_Groupings ON Plot_Types.ID = PlotType_Groupings.PlotType_ID 
    WHERE PlotType_Groupings.PlotGroup_ID = ?
");
$plotTypeStmt->bind_param('i', $superPlotGroup_ID);
$plotTypeStmt->execute();
$plotTypeResult = $plotTypeStmt->get_result();

$plotTypes = [];
while ($typeRow = $plotTypeResult->fetch_assoc()) {
    $plotTypes[] = [
        'Name' => $typeRow['Name'],
        'Description' => $typeRow['Description'],
        'DisplayName' => $typeRow['DisplayName']
    ];
}

$data = [];
foreach ($plotGroups as $key => $value) {
    $data[$key] = $value;
    $data['PlotTypes'] = $plotTypes;

}

header('Content-Type: application/json');
echo json_encode($data);

$result->free();
$plotTypeResult->free();
$stmt->close();
$plotTypeStmt->close();
?>