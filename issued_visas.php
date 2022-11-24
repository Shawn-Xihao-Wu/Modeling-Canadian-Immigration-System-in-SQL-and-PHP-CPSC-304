<html>
  <?php
    //this tells the system that it's no longer just parsing html; it's now parsing PHP

    $success = True; //keep track of errors so it redirects the page only if there are no errors
    $db_conn = NULL; // edit the login credentials in connectToDB()
    $show_debug_alert_messages = False; // set to True if you want alerts to show you which methods are being triggered (see how it is used in debugAlertMessage())
    $viewAllStatement = "";
    $countAllStatement = "";
    $columns = array(
        "Reason"            => $_POST['attr1'],
        "WorkType"          => $_POST['attr2'],
        "InstitutionID"     => $_POST['attr3'],
        "Destination"       => $_POST['attr4']
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

    function printAllTuples($result)
    { //prints results from a select statement
        $statement = "";
        $statement .= "<br>Retrieving data...<br>";
        $statement .= "<table>";
        $statement .= "<tr><th>VisaID</th><th>VisaType</th><th>ApplicationID</th><th>ECID</th></tr>";

        while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
            $statement .= "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td>" . $row[3] . "</td></tr>"; //or just use "echo $row[0]"
        }

        $statement .= "</table>";

        return $statement;
    }

    function printTravelRecordTuples($result)
    { //prints results from a select statement
        $statement = "";
        $statement .= "<br>Retrieving data...<br>";
        $statement .= "<table>";
        $statement .= "<tr><th>VisaID</th></tr>";

        while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
            $statement .= "<tr><td>" . $row[0] . "</td></tr>"; //or just use "echo $row[0]"
        }

        $statement .= "</table>";

        return $statement;
    }

    function printSelectedTuples($result)
    { //prints results from a select statement
        global $columns, $numOfColumns;

        $statement = "";
        $statement .= "<br>Retrieving data...<br>";
        $statement .= "<table>";
        $statement .= "<tr><th>Selected Attribute</th></tr>";

        while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
            $statement .= "<tr><td>" . $row[0] . "</td></tr>"; //or just use "echo $row[0]"
        }

        $statement .= "</table>";

        return $statement;
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

    function checkColumnNum()
    {
        global $numOfColumns, $columns;
        foreach ($columns as $x => $column) {
            if (!empty($column)) {
                $numOfColumns++;
            }
        }
    }

    function searchQueryGenerator()
    {
        global $columns;
        $query = "SELECT DISTINCT";
    
        foreach($columns as $x => $column) {
            if (!empty($column)) {
                $query .= " " . $column . ","; 
            }
        }

        $query = substr($query, 0, -1);
        $visa = trim($_POST['visa']);
        $searchItem = trim($_POST['searchItem']);
        $input = trim($_POST['input']);
        $query .= " FROM " .$visa. " v, VisaFromIssue vf WHERE v.VisaID=vf.VisaID AND vf." .$searchItem. " = '" .$input. "'";

        return $query;
    }

    function handleSearchRequest()
    {
        global $db_conn, $viewSelectedStatement, $numOfColumns;

        checkColumnNum();

        $visa = trim($_POST['visa']);
        $attribute = trim($_POST['attribute']);
        $searchItem = trim($_POST['searchItem']);
        $input = trim($_POST['input']);

        $result = executePlainSQL(searchQueryGenerator());
        
        $viewSelectedStatement = printSelectedTuples($result); 
    }

    function handleCountRequest()
    {
        global $db_conn, $countAllStatement;

        $result = executePlainSQL("SELECT Count(*) FROM VisaFromIssue");
        if (($row = oci_fetch_row($result)) != false) {
            $countAllStatement = $countAllStatement . "<br> The total number of issued visas is: " . $row[0] . "<br>";
        }
    }

    function handleViewAllRequest()
    {
        global $db_conn, $viewAllStatement;

        $result = executePlainSQL("SELECT * FROM VisaFromIssue");
        
        $viewAllStatement = printAllTuples($result);   
    }

    function handleViewTravelRecordRequest()
    {
        global $db_conn, $viewTravelRecordStatement;

        $result = executePlainSQL("SELECT VisaID 
                                    FROM TravelHistoryRecordsTravelsBy t
                                    WHERE NOT EXISTS (SELECT h.VisaID
                                                        FROM Holds h
                                                        WHERE NOT EXISTS (SELECT *
                                                                            FROM Applicants a
                                                                            WHERE h.ApplicantID=a.ApplicantID
                                                                            AND t.VisaID=h.VisaID
                                                                            AND a.Nationality='China'))");
        
        $viewTravelRecordStatement = printTravelRecordTuples($result);   
    }

    // HANDLE ALL POST ROUTES
    // A better coding practice is to have one method that reroutes your requests accordingly. It will make it easier to add/remove functionality.
    function handlePOSTRequest()
    {
        if (connectToDB()) {
            if (array_key_exists('searchQueryRequest', $_POST)) {
                handleSearchRequest();
            }

            disconnectFromDB();
        }
    }

    // HANDLE ALL GET ROUTES
    // A better coding practice is to have one method that reroutes your requests accordingly. It will make it easier to add/remove functionality.
    function handleGETRequest()
    {
        if (connectToDB()) {
            if (array_key_exists('countTuples', $_GET)) {
                handleCountRequest();
            } else if (array_key_exists('viewAllTuples', $_GET)) {
                handleViewAllRequest();
            } else if (array_key_exists('viewTravelRecord', $_GET)) {
                handleViewTravelRecordRequest();
            }

            disconnectFromDB();
        }
    }

    if (isset($_POST['searchSubmit'])) {
        handlePOSTRequest();
    } else if (isset($_GET['countTupleRequest']) || isset($_GET['viewAllTupleRequest']) || isset($_GET['viewTravelRecordRequest'])) {
        handleGETRequest();
    }
    ?>

  <head>
      <title>Issued Visas</title>
  </head>

  <body>

      <h2>Search for visa:</h2>
      <form method="POST" action="issued_visas.php">
          <!--refresh page when submitted-->
          <input type="hidden" id="searchQueryRequest" name="searchQueryRequest">

          <label for="visa">Choose a visa: </label>
          <select id="visa" name="visa" class="form-control">
              <option value="AsylumRefugeeVisa">AsylumRefugeeVisa</option>
              <option value="WorkVisaSponseredBy">WorkVisa</option>
              <option value="StudentVisaVerifiedBy">StudentVisa</option>
              <option value="TouristVisa">TouristVisa</option>
          </select> <br /><br />

        <p>Select the column names of the table (<em>please select at least one</em>):</p>
            <input type="checkbox" id="attr1" name="attr1" value="Reason">
            <label for="vehicle1"> Reason </label>
            <input type="checkbox" id="attr2" name="attr2" value="WorkType">
            <label for="vehicle2"> WorkType </label>
            <input type="checkbox" id="attr3" name="attr3" value="InstitutionID">
            <label for="vehicle3"> InstitutionID </label>
            <input type="checkbox" id="attr4" name="attr4" value="Destination">
            <label for="vehicle3"> Destination </label>
            <br /><br />

          <label for="searchItem">Search by: </label>
          <select id="searchItem" name="searchItem" class="form-control">
              <option value="VisaID">VisaID</option>
              <option value="ApplicationID">ApplicationID</option>
              <option value="ECID">ECID</option>
          </select> <br /><br />
          
          Input: <input type="text" name="input"> <br /><br />
          </select> <br /><br />

          <input type="submit" value="Search" name="searchSubmit"></p>
      </form>

      <?php echo $viewSelectedStatement ?>

      <hr />

      <h2>Count All the issued visas</h2>
      <form method="GET" action="issued_visas.php">
          <!--refresh page when submitted-->
          <input type="hidden" id="countTupleRequest" name="countTupleRequest">
          <input type="submit" value="Count" name="countTuples"></p>
      </form>
      <?php echo $countAllStatement ?>

      <hr />
      <h2>View All the issued visas</h2>
      <form method="GET" action="issued_visas.php">
          <!--refresh page when submitted-->
          <input type="hidden" id="viewAllTupleRequest" name="viewAllTupleRequest">
          <input type="submit" value="View" name="viewAllTuples"></p>
      </form>
      <?php echo $viewAllStatement ?>

      <hr />

      <p>
          <a href="index.php">
              <button class="button button2">Back</button>
        </a>
        </p>

	</body>
</html>