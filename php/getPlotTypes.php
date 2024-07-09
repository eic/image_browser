<?php
require_once('./configurations.php');

$servername = $FEconfig['dbhost'];
$username = $FEconfig['dbuser'];
$password = $FEconfig['dbpass'];
$dbname = $FEconfig['dbname'];

$IsChunked = true;
$plotGroupId = $_GET['plotGroupId'];

#if $FEconfig contains a key named "UseChunkedImages"
if (array_key_exists('UseChunkedImages', $FEconfig)) {
    if ($FEconfig['UseChunkedImages'] == "false") {
        $IsChunked = false;
    } 
}

//$IsChunked = $FEconfig['UseChunkedImages'];

//echo $_GET['qs'] . " ---> " . $_GET['qe'];
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($IsChunked) {
    $sql="SELECT * from Plot_Types where IsChunked=1 and IgnorePlot=0 and PlotGroup=$plotGroupId Order by Active_Model_ID desc;";
} else {
    $sql="SELECT * from Plot_Types where IsChunked=0 and IgnorePlot=0 and PlotGroup=$plotGroupId Order by Active_Model_ID desc;";
}
#echo $sql . "<br>";

$result = $conn->query($sql);
$data = array();
if ($result->num_rows > 0) {
// output data of each row
    while($row = $result->fetch_assoc()) {
        $data[]=$row;
     //echo "id: " . $row["id"]. " - Run: " . $row["run"]. "<br>";
    }
} 
$conn->close();

echo json_encode($data);
return json_encode($data);
?>