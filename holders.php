<html>
<?php
//this tells the system that it's no longer just parsing html; it's now parsing PHP

$success = True; //keep track of errors so it redirects the page only if there are no errors
$db_conn = NULL; // edit the login credentials in connectToDB()
$show_debug_alert_messages = False; // set to True if you want alerts to show you which methods are being triggered (see how it is used in debugAlertMessage())
$viewTravelStatement = "";
$viewAllHolderStatement = "";
$numOfColumns = 0;
$columns = array(
    "ApplicantID"       => $_GET['attr1'],
    "Name"              => $_GET['attr2'],
    "Nationality"       => $_GET['attr3'],
    "VisaID"            => $_GET['attr4'],
    "Issue Date"        => $_GET['attr5'],
    "Expiration Date"   => $_GET['attr6']
);

function debugAlertMessage($message)
{
    global $show_debug_alert_messages;

    if ($show_debug_alert_messages) {
        echo "<script type='text/javascript'>alert('" . $message . "');</script>";
    }
}

function executePlainSQL($cmdstr)
{ //takes a plain (no bound variables) SQL command and executes it
    //echo "<br>running ".$cmdstr."<br>";
    global $db_conn, $success;

    $statement = OCIParse($db_conn, $cmdstr);
    //There are a set of comments at the end of the file that describe some of the OCI specific functions and how they work

    if (!$statement) {
        echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
        $e = OCI_Error($db_conn); // For OCIParse errors pass the connection handle
        echo htmlentities($e['message']);
        $success = False;
    }

    $r = OCIExecute($statement, OCI_DEFAULT);
    if (!$r) {
        echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
        $e = oci_error($statement); // For OCIExecute errors pass the statementhandle
        echo htmlentities($e['message']);
        $success = False;
    }

    return $statement;
}

function executeBoundSQL($cmdstr, $list)
{
    /* Sometimes the same statement will be executed several times with different values for the variables involved in the query.
		In this case you don't need to create the statement several times. Bound variables cause a statement to only be
		parsed once and you can reuse the statement. This is also very useful in protecting against SQL injection.
		See the sample code below for how this function is used */

    global $db_conn, $success;
    $statement = OCIParse($db_conn, $cmdstr);

    if (!$statement) {
        echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
        $e = OCI_Error($db_conn);
        echo htmlentities($e['message']);
        $success = False;
    }

    foreach ($list as $tuple) {
        foreach ($tuple as $bind => $val) {
            //echo $val;
            //echo "<br>".$bind."<br>";
            OCIBindByName($statement, $bind, $val);
            unset($val); //make sure you do not remove this. Otherwise $val will remain in an array object wrapper which will not be recognized by Oracle as a proper datatype
        }

        $r = OCIExecute($statement, OCI_DEFAULT);
        if (!$r) {
            echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
            $e = OCI_Error($statement); // For OCIExecute errors, pass the statementhandle
            echo htmlentities($e['message']);
            echo "<br>";
            $success = False;
        }
    }
}

function connectToDB()
{
    global $db_conn;

    // Your username is ora_(CWL_ID) and the password is a(student number). For example,
    // ora_platypus is the username and a12345678 is the password.
    $account = file_get_contents('account.txt');
    $lines = explode(",", $account);
    $db_conn = OCILogon(trim($lines[0]), trim($lines[1]), trim($lines[2]));

    if ($db_conn) {
        debugAlertMessage("Database is Connected");
        return true;
    } else {
        debugAlertMessage("Cannot connect to Database");
        $e = OCI_Error(); // For OCILogon errors pass no handle
        echo htmlentities($e['message']);
        return false;
    }
}

function disconnectFromDB()
{
    global $db_conn;

    debugAlertMessage("Disconnect from Database");
    OCILogoff($db_conn);
}

function printTravelTuples($result)
{ //prints results from a select statement
    $statement = "";
    $statement .=  "Retrieving data...";
    $statement .= "<table>";
    $statement .= "<tr><th>RecordID</th><th>TimeOfTravel</th><th>Destination</th><th>Departure</th><th>VisaID</th></tr>";

    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        $statement .= "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td>" . $row[3] . "</td><td>" . $row[4] . "</td></tr>"; 
    }

    $statement .= "</table>";

    return $statement;
}


function handleViewTravelTupleRequest()
{
    global $db_conn, $viewTravelStatement;

    $AID = trim($_POST['ApplicantID']);
    

    $result = executePlainSQL("SELECT * FROM TravelHistoryRecordsTravelsBy WHERE VisaID = ANY (SELECT VisaID FROM Holds WHERE ApplicantID = '" . $AID . "')");


    $viewTravelStatement = printTravelTuples($result);
}

function printHolderTuples($result)
{
    global $columns, $numOfColumns;
    $statement = "";
    $statement .= "Retrieving data...";
    $statement .= "<table><tr>";

    foreach ($columns as $x => $column) {
        if(!empty($column)) {
            $statement .= "<th>" . $x . "</th>";
        }
    }
    $statement .= "</tr>";
    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        $statement .= "<tr>";
        for ($i = 0; $i<$numOfColumns; $i++) {
            $statement .= "<td>" . $row[$i] . "</td>";
        }
        $statement .= "</tr>";
    }

    $statement .= "</tr></table>";

    return $statement;
}

//"SELECT DISTINCT H.ApplicantID, A.NameOfApplicants, A.Nationality, H.VisaID, H.IssueDate, H.ExpirationDate FROM Holds H, Applicants A WHERE H.ApplicantID = A.ApplicantID";
function holderQueryGenerator()
{
    global $columns;
    $query = "SELECT DISTINCT";
    
    foreach($columns as $x => $column) {
        if (!empty($column)) {
            $query .= " " . $column . ","; 
        }
    }

    $query = substr($query, 0, -1);
    $query .= " FROM Holds, Applicants WHERE Holds.ApplicantID = Applicants.ApplicantID";

    return $query;
}

function checkColumnNum()
{
    global $numOfColumns, $columns;
    foreach ($columns as $x => $column) {
        if (!empty($column)) {
            $numOfColumns++;
        }
    }
}

function handleViewAllHolderRequest()
{
    global $db_conn, $viewAllHolderStatement, $numOfColumns;

    checkColumnNum();

    if ($numOfColumns != 0) {
        $result = executePlainSQL(holderQueryGenerator());
        $viewAllHolderStatement = printHolderTuples($result);
    } else {
        $viewAllHolderStatement = "ERROR: Please select at least one column name!";
    }
}

// HANDLE ALL POST ROUTES
// A better coding practice is to have one method that reroutes your requests accordingly. It will make it easier to add/remove functionality.
function handlePOSTRequest()
{
    if (connectToDB()) {
        if (array_key_exists('viewTravelTuples', $_POST)) {
            handleViewTravelTupleRequest();
        }

        disconnectFromDB();
    }
}

// HANDLE ALL GET ROUTES
// A better coding practice is to have one method that reroutes your requests accordingly. It will make it easier to add/remove functionality.
function handleGETRequest()
{
    if (connectToDB()) {
        if (array_key_exists('viewAllHolders', $_GET)) {
            handleViewAllHolderRequest();
        }

        disconnectFromDB();
    }
}

if (isset($_POST['viewTravelTupleRequest'])) {
    handlePOSTRequest();
} else if (isset($_GET['viewAllHolderRequest'])) {
    handleGETRequest();
}
?>

<head>
    <title>Visa holders</title>
</head>

<body>

    <h2>View All Visa-Holders </h2>
    <p>Select the column names of the table (<em>please select at least one</em>):</p>
    <form method="GET" action="holders.php">
        <!--refresh page when submitted-->
        <input type="hidden" id="viewAllHolderRequest" name="viewAllHolderRequest">
        <input type="checkbox" id="attr1" name="attr1" value="Holds.ApplicantID">
        <label for="vehicle1"> ApplicantID </label>
        <input type="checkbox" id="attr2" name="attr2" value="NameOfApplicants">
        <label for="vehicle2"> Name </label>
        <input type="checkbox" id="attr3" name="attr3" value="Nationality">
        <label for="vehicle3"> Nationality </label>
        <input type="checkbox" id="attr4" name="attr4" value="VisaID">
        <label for="vehicle3"> VisaID </label>
        <input type="checkbox" id="attr5" name="attr5" value="IssueDate">
        <label for="vehicle3"> Issue Date </label>
        <input type="checkbox" id="attr6" name="attr6" value="ExpirationDate">
        <label for="vehicle3"> Expiration Date </label>
        <br>
        <br>
        <input type="submit" value="View" name="viewAllHolders"></p>
    </form>
    <?php echo $viewAllHolderStatement; ?>
    <hr />

    <h2>View Travel History</h2>
    <p>Please use ApplicantID to identify the individual whose travel history you want to see <em>(click "View" above to see for ApplicantID)</em>.</p>
    <form method="POST" action="holders.php">
        <!--refresh page when submitted-->
        ApplicantID: <input type="text" name="ApplicantID"> <br /><br />
        <input type="hidden" id="viewTravelTupleRequest" name="viewTravelTupleRequest">
        <input type="submit" value="View" name="viewTravelTuples"></p>
    </form>
    <?php echo $viewTravelStatement; ?>

    <hr />
    <p>
        <a href="index.php">
            <button class="button button2">Back</button>
        </a>
    </p>
</body>

</html>