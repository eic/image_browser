<?php
require_once('./configurations.php');

$servername = $FEconfig['dbhost'];
$username = $FEconfig['dbuser'];
$password = $FEconfig['dbpass'];
$dbname = $FEconfig['dbname'];

if (array_key_exists('UseChunkedImages', $FEconfig)) {
    if ($FEconfig['UseChunkedImages'] == "false") {
        $IsChunked = false;
    } 
}

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// pass the plot name as a url parameter, allow list of plot names
$plotName = $_GET['plotName'];
$IsChunked = isset($_GET['IsChunked']) ? (int) $_GET['IsChunked'] : 0;
$plotNames = explode(",", $plotName);
$plotNames = array_map('trim', $plotNames);

// Prepare the query to get plot information
$plotNamesPlaceholders = implode(',', array_fill(0, count($plotNames), '?'));
$query = "
    SELECT Plots.*, Plot_Types.Name AS PlotTypeName, Plot_Types.Description AS Description, Plot_Types.DisplayName AS DisplayName
    FROM Plots
    JOIN Plot_Types ON Plots.Plot_Types_ID = Plot_Types.ID
    WHERE Plot_Types.Name IN ($plotNamesPlaceholders) and Plot_Types.IsChunked= ?
";

// Prepare and bind parameters
$stmt = mysqli_prepare($conn, $query);
if ($stmt === false) {
    die("MySQL prepare statement error: " . mysqli_error($conn));
}

// Create a dynamic list of parameters for the prepared statement
$typeStr = str_repeat('s', count($plotNames)) . 'i';
$params = array_merge($plotNames, [$IsChunked]);

mysqli_stmt_bind_param($stmt, $typeStr, ...$params);


// Execute the query
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch and prepare the results
$plots = [];
while ($row = mysqli_fetch_assoc($result)) {
    $plot = [
        'ID' => $row['ID'],
        'PlotTypeID' => $row['Plot_Types_ID'],
        'ImagePath' => $row['RunPeriod'],
        'PlotTypeName' => $row['PlotTypeName'],
        'Description' => $row['Description'],
        'DisplayName' => $row['DisplayName']
    ];
    $plots[] = $plot;
}

// Output the results as JSON
header('Content-Type: application/json');
echo json_encode($plots);

// Close the statement and connection
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>