<?php
require_once('./configurations.php');

$servername = $FEconfig['dbhost'];
$username = $FEconfig['dbuser'];
$password = $FEconfig['dbpass'];
$dbname = $FEconfig['dbname'];

$plotGroupId = $_GET['plotGroupId'];

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT PlotType_Groupings.PlotType_ID, Plot_Types.Name
FROM PlotType_Groupings
INNER JOIN Plot_Types ON PlotType_Groupings.PlotType_ID = Plot_Types.ID
WHERE PlotType_Groupings.PlotGroup_ID = " . $plotGroupId . "
ORDER BY PlotType_Groupings.PlotType_ID;";

$result = $conn->query($sql);
$data = array();
if ($result->num_rows > 0) {
// output data of each row
    while($row = $result->fetch_assoc()) {
        $data[]=$row;
    }
} 
$conn->close();

echo json_encode($data);
return json_encode($data);
?>