<!-- Modified from https://www.students.cs.ubc.ca/~cs-304/resources/php-oracle-resources/php-setup.html -->
<!DOCTYPE html>
<html>
<?php
    //this tells the system that it's no longer just parsing html; it's now parsing PHP

    $success = True; //keep track of errors so it redirects the page only if there are no errors
    $db_conn = NULL; // edit the login credentials in connectToDB()
    $show_debug_alert_messages = False; // set to True if you want alerts to show you which methods are being triggered (see how it is used in debugAlertMessage())
    $resetStatement = "";

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

    function startsWith($haystack, $needle)
    {
      $length = strlen($needle);
      return (substr($haystack, 0, $length) === $needle);
    }

    // Modified from https://stackoverflow.com/questions/4027769/running-mysql-sql-files-in-php
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
      global $db_conn, $resetStatement;

      $resetStatement .= "Starting/Reseting...";
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

<head>
  <meta charset="UTF-8">
  <title>Immigration Management</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="script.js"></script>
</head>

<style>
  div {text-align: center;}
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

  .button2 {
    background-color: #5b98b3;
    width: 300px;
  }
</style>

<body>
  <div id="content">

    <h1>🇨🇦 Canadian Immigration Management System 🇨🇦</h1>

    <img src="flag.png" 
    style="position:fixed; right:950px; bottom:200px; width:200px; height:100px; border:none;"
    alt="flag" width="600" height="300">

    <h2>Start/Reset</h2>

    <form method="POST" action="index.php">
      <!-- if you want another page to load after the button is clicked, you have to specify that page in the action parameter -->
      <input type="hidden" id="resetTablesRequest" name="resetTablesRequest">
      <p><input type="submit" value="Start/Reset" name="start/reset"></p>
    </form>
    <?php echo $resetStatement ?>


    <h2>Manage Visas</h2>

    <p>
      <a href="issued_visas.php">
        <button class="button button2">Issued visas</button>
      </a>
    </p>
    <p>
      <a href="approved_institutions.php">
        <button class="button button2">Approved institutions</button>
      </a>
    </p>


    <h2>Manage People</h2>
    <p>
      <a href="applicants.php">
        <button class="button button2">Applicants</button>
      </a>
    </p>
    <p>
      <a href="holders.php">
        <button class="button button2">Visa-holders</button>
      </a>
    </p>


    <h2>Manage Embassy & Consulates</h2>
    
    <p>
      <a href="embassy_consulates.php">
        <button class="button button2">Embassys/consulates</button>
      </a>
    </p>


    <p><a href="https://github.students.cs.ubc.ca/CPSC304-2022W-T1/project_f3a3g_o7c8d_t5h6p"><i class="fa fa-github" style="font-size:36px;color:#C8AE81"></i></a></p>
  </div>
</body>

</html>