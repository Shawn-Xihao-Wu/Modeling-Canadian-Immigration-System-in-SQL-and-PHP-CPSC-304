<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Immigration Management</title>
  <link rel="stylesheet" href="style.css">
  <script src="script.js"></script>
</head>

<style>
  .button {
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
  }

  .button1 {
    background-color: #4CAF50;
    width: 300px;
  }

  /* Green */
  .button2 {
    background-color: #008CBA;
    width: 300px;
  }

  /* Blue */
</style>

<body>
  <div id="content">
    <p><a href="https://github.students.cs.ubc.ca/CPSC304-2022W-T1/project_f3a3g_o7c8d_t5h6p">Github</a></p>

    <h2>Start/Reset</h2>
    <p>If you wish to reset the table press on the reset button. If this is the first time you're running this page, you MUST use reset</p>

    <form method="POST" action="index.php">
      <!-- if you want another page to load after the button is clicked, you have to specify that page in the action parameter -->
      <input type="hidden" id="resetTablesRequest" name="resetTablesRequest">
      <p><input type="submit" value="Start/Reset" name="start/reset"></p>
    </form>

    <p>
      <a href="oracle-test.php">
        <button class="button button1">See sample project</button>
      </a>
    </p>
    <h1>Manage Visas</h1>

    <p>
      <a href="issued-visa.html">
        <button class="button button2">Manage issued visas</button>
      </a>
    </p>
    <p>
      <a href="approved_institution.php">
        <button class="button button2">Manage approved institutions</button>
      </a>
    </p>


    <h1>Manage People</h1>
    <p>
      <a href="applicants.php">
        <button class="button button2">Applicants</button>
      </a>
    </p>
    <p>
      <a href="issued-visa.html">
        <button class="button button2">Manage visa-holders</button>
      </a>
    </p>


    <h1>Manage embassys/consulates</h1>
    <p>
      <a href="EmbassyConsulate.php">
        <button class="button button2">Manage embassys/consulates</button>
      </a>
    </p>

    <?php
    //this tells the system that it's no longer just parsing html; it's now parsing PHP

    $success = True; //keep track of errors so it redirects the page only if there are no errors
    $db_conn = NULL; // edit the login credentials in connectToDB()
    $show_debug_alert_messages = False; // set to True if you want alerts to show you which methods are being triggered (see how it is used in debugAlertMessage())

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

    function connectToDB()
    {
      global $db_conn;

      // Your username is ora_(CWL_ID) and the password is a(student number). For example,
      // ora_platypus is the username and a12345678 is the password.
      //$db_conn = OCILogon("ora_wkyi2021", "a39028535", "dbhost.students.cs.ubc.ca:1522/stu");
      $db_conn = OCILogon("ora_wkyi2021", "a39028535", "dbhost.students.cs.ubc.ca:1522/stu");

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

    function startsWith($haystack, $needle)
    {
      $length = strlen($needle);
      return (substr($haystack, 0, $length) === $needle);
    }

    function run_sql_file($location)
    {
      //load file
      $commands = file_get_contents($location);

      //delete comments
      $lines = explode("\n", $commands);
      $commands = '';
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line && !startsWith($line, '--')) {
          $commands .= $line . "\n";
        }
      }

      //convert to array
      $commands = explode(";", $commands);
      
      //run commands
      foreach ($commands as $command) {
        if(trim($command))
        {
            executePlainSQL($command);
        }
      }
    }

    function handleResetRequest()
    {
      global $db_conn;

      echo "<br> Starting/Reseting... <br>";
      run_sql_file('project.sql');

      OCICommit($db_conn);
    }

    // HANDLE ALL POST ROUTES
    // A better coding practice is to have one method that reroutes your requests accordingly. It will make it easier to add/remove functionality.
    function handlePOSTRequest()
    {
      if (connectToDB()) {
        if (array_key_exists('resetTablesRequest', $_POST)) {
          handleResetRequest();
        }
        disconnectFromDB();
      }
    }

    if (isset($_POST['start/reset'])) {
      handlePOSTRequest();
    }
    ?>
</body>
</div>
</html>