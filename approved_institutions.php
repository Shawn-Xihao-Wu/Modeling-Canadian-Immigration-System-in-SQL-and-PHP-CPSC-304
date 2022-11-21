  <html>
  <?php
    //this tells the system that it's no longer just parsing html; it's now parsing PHP

    $success = True; //keep track of errors so it redirects the page only if there are no errors
    $db_conn = NULL; // edit the login credentials in connectToDB()
    $show_debug_alert_messages = False; // set to True if you want alerts to show you which methods are being triggered (see how it is used in debugAlertMessage())
    $viewAllStatement = "";
    $countAllStatement = "";
    $deleteStatement = "";

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
        $statement .= "<tr><th>Institution ID</th><th>Name</th><th>Category</th></tr>";

        while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
            $statement .= "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td></tr>"; //or just use "echo $row[0]"
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

    function handleDeleteRequest()
    {
        global $db_conn, $deleteStatement;

        $toDelete = $_POST['InstitutionIDToDelete'];

        executePlainSQL("DELETE FROM ApprovedInstitutions WHERE InstitutionID='" . $toDelete . "'");
        $deleteStatement = "Deleted the institution with ID ". $toDelete . "!";
        OCICommit($db_conn);
    }

    function handleInsertRequest()
    {
        global $db_conn;

        //Getting the values from user and insert data into the table
        $tuple = array(
            ":bind1" => $_POST['InstitutionID'],
            ":bind2" => $_POST['InstitutionName'],
            ":bind3" => $_POST['Category']
        );

        $alltuples = array(
            $tuple
        );

        executeBoundSQL("INSERT into ApprovedInstitutions VALUES (:bind1, :bind2, :bind3)", $alltuples);
        OCICommit($db_conn);
    }

    function handleCountRequest()
    {
        global $db_conn, $countAllStatement;

        $result = executePlainSQL("SELECT Count(*) FROM ApprovedInstitutions");
        if (($row = oci_fetch_row($result)) != false) {
            $countAllStatement = $countAllStatement . "<br> The total number of approved institutions is: " . $row[0] . "<br>";
        }
    }

    function handleViewAllRequest()
    {
        global $db_conn, $viewAllStatement;

        $result = executePlainSQL("SELECT * FROM ApprovedInstitutions");

        $viewAllStatement = printAllTuples($result);
    }

    // HANDLE ALL POST ROUTES
    // A better coding practice is to have one method that reroutes your requests accordingly. It will make it easier to add/remove functionality.
    function handlePOSTRequest()
    {
        if (connectToDB()) {
            if (array_key_exists('deleteQueryRequest', $_POST)) {
                handleDeleteRequest();
            } else if (array_key_exists('insertQueryRequest', $_POST)) {
                handleInsertRequest();
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
            }

            disconnectFromDB();
        }
    }

    if (isset($_POST['deleteSubmit']) || isset($_POST['insertSubmit'])) {
        handlePOSTRequest();
    } else if (isset($_GET['countTupleRequest']) || isset($_GET['viewAllTupleRequest'])) {
        handleGETRequest();
    }
    ?>

  <head>
      <title>Approved Institution</title>
  </head>

  <body>

      <h2>View All the Approved Institutions</h2>
      <form method="GET" action="approved_institutions.php">
          <!--refresh page when submitted-->
          <input type="hidden" id="viewAllTupleRequest" name="viewAllTupleRequest">
          <input type="submit" value="View" name="viewAllTuples"></p>
      </form>
      <?php echo $viewAllStatement ?>

      <hr />

      <h2>Count All the Approved Institutions</h2>
      <form method="GET" action="approved_institutions.php">
          <!--refresh page when submitted-->
          <input type="hidden" id="countTupleRequest" name="countTupleRequest">
          <input type="submit" value="Count" name="countTuples"></p>
      </form>
      <?php echo $countAllStatement ?>

      <hr />

      <h2>Insert New Approved Institutions</h2>
      <form method="POST" action="approved_institutions.php">
          <!--refresh page when submitted-->
          <input type="hidden" id="insertQueryRequest" name="insertQueryRequest">
          InstitutionID: <input type="text" name="InstitutionID"> <br /><br />
          InstitutionName: <input type="text" name="InstitutionName"> <br /><br />
          <label for="Category">Choose a category: </label>
          <select id="Category" name="Category" class="form-control">
              <option value="Company">Company</option>
              <option value="University/College">University/College</option>
          </select> <br /><br />

          <input type="submit" value="Insert" name="insertSubmit"></p>
      </form>

      <hr />

      <h2>Delete Institutions</h2>
      <form method="POST" action="approved_institutions.php">
          <!--refresh page when submitted-->
          <input type="hidden" id="deleteQueryRequest" name="deleteQueryRequest">
          InstitutionID: <input type="text" name="InstitutionIDToDelete"> <br /><br />
          <input type="submit" value="Delete" name="deleteSubmit"></p>
      </form>
      <?php echo $deleteStatement ?>

      <hr />

      <p>
          <a href="index.php">
              <button class="button button2">Back</button>
          </a>
      </p>

  </body>

  </html>