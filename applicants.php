<!-- Modified from https://www.students.cs.ubc.ca/~cs-304/resources/php-oracle-resources/php-setup.html -->
<html>
<?php
//this tells the system that it's no longer just parsing html; it's now parsing PHP

$success = True; //keep track of errors so it redirects the page only if there are no errors
$db_conn = NULL; // edit the login credentials in connectToDB()
$show_debug_alert_messages = False; // set to True if you want alerts to show you which methods are being triggered (see how it is used in debugAlertMessage())
$viewAllStatement = "";
$viewGroupByStatement = "";
$viewAverageStatement = "";
$viewNestedStatement = "";
$viewDivisionStatement = "";

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
    $statement .=  "Retrieving data...";
    $statement .= "<table>";
    $statement .= "<tr><th>ApplicantID</th><th>NameOfApplicants</th><th>Nationality</th><th>DateOfBirth</th></tr>";

    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        $statement .= "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td>" . $row[3] . "</td></tr>"; //or just use "echo $row[0]"
    }

    $statement .= "</table>";

    return $statement;
}

function printGroupByTuples($result)
{
    $statement = "";
    $statement .= "<table>";
    $statement .= "<tr><th>Nationality</th><th> Count of People older than 50 </th></tr>";

    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        $statement .= "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td></tr>"; //or just use "echo $row[0]"
    }

    $statement .= "</table>";

    return $statement;
}

function printAverageTuples($result)
{
    $statement = "";
    $statement .= "<table>";
    $statement .= "<tr><th>Nationality</th><th> Average age </th></tr>";

    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        $statement .= "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td></tr>"; //or just use "echo $row[0]"
    }

    $statement .= "</table>";

    return $statement;
}

function printDivisionTuples($result)
{
    $statement = "";
    $statement .= "<table>";
    $statement .= "<tr><th>ApplicantID</th><th>Name</th><th>Nationality</th><th>Date of Birth</th></tr>";
    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        $statement .= "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td>" . $row[3] . "</td></tr>"; //or just use "echo $row[0]"
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

function handleUpdateRequest()
{
    global $db_conn;

    $ApplicantID = $_POST['ApplicantID'];
    $newValue = $_POST['newValue'];
    $UpdateOptions = $_POST['UpdateOptions'];

    if ($UpdateOptions == "Update name") {
        $UpdateOptions = "NameOfApplicants";
    } else if ($UpdateOptions == "Update DateOfBirth") {
        $UpdateOptions = "DateOfBirth";
    } else if ($UpdateOptions == "Update Nationality") {
        $UpdateOptions = "Nationality";
    }


    // UPDATE
    executePlainSQL("UPDATE Applicants SET " . $UpdateOptions .  "= '" . $newValue . "' WHERE ApplicantID ='" . $ApplicantID . "'");
    OCICommit($db_conn);
}


function handleInsertRequest()
{
    global $db_conn;

    //Getting the values from user and insert data into the table
    $tuple = array(
        ":bind1" => $_POST['anumber'],
        ":bind2" => $_POST['aname'],
        ":bind3" => $_POST['country'],
        ":bind4" => $_POST['abirthday']
    );

    $alltuples = array(
        $tuple
    );

    executeBoundSQL("insert into Applicants values (:bind1, :bind2, :bind3, :bind4)", $alltuples);
    OCICommit($db_conn);
}

function handleViewAllRequest()
{

    global $db_conn, $viewAllStatement;

    $result = executePlainSQL("SELECT * FROM Applicants ORDER BY Nationality");

    $viewAllStatement = printAllTuples($result);
}


function handleGroupByRequest()
{

    global $db_conn, $viewGroupByStatement;

    // GROUP BY having
    $result = executePlainSQL("SELECT Nationality, COUNT(*) FROM Applicants 
    WHERE (CURRENT_DATE - DateOfBirth)/365 > 50 group by nationality HAVING COUNT(*) > 0 ORDER BY Nationality");

    $viewGroupByStatement = printGroupByTuples($result);
}

function handleAverageRequest()
{
    global $db_conn, $viewAverageStatement;

    // GROUP BY
    $result = executePlainSQL("SELECT Nationality, FLOOR(AVG((CURRENT_DATE - DateOfBirth)/365)) 
    FROM Applicants GROUP BY Nationality ORDER BY Nationality");

    $viewAverageStatement = printAverageTuples($result);
}

function handleNestedRequest()
{
    global $db_conn, $viewNestedStatement;

    // GROUP BY NESTED
    $result = executePlainSQL("SELECT A.Nationality, FLOOR(AVG((CURRENT_DATE - A.DateOfBirth)/365)) 
    FROM Applicants A GROUP BY A.Nationality HAVING 3 < (SELECT COUNT(*) FROM Applicants A2 WHERE A.Nationality = A2.Nationality)");

    $viewNestedStatement = printAverageTuples($result);
}

function handleDivisionRequest()
{
    global $db_conn, $viewDivisionStatement;

    $query = "  SELECT * 
                FROM Applicants A
                WHERE NOT EXISTS (  SELECT V1.VisaType
                                    FROM VisaFromIssue V1
                                    MINUS 
                                    (   SELECT V2.VisaType
                                        FROM Creates C, VisaFromIssue V2
                                        WHERE A.ApplicantID = C.ApplicantID AND C.ApplicationID = V2.ApplicationID
                                        ))";

    $result = executePlainSQL($query);

    $viewDivisionStatement = printDivisionTuples($result);
}


// HANDLE ALL POST ROUTES
// A better coding practice is to have one method that reroutes your requests accordingly. It will make it easier to add/remove functionality.
function handlePOSTRequest()
{
    if (connectToDB()) {
        if (array_key_exists('updateQueryRequest', $_POST)) {
            handleUpdateRequest();
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
        if (array_key_exists('viewAllTuples', $_GET)) {
            handleViewAllRequest();
        } else if (array_key_exists('viewGroupByHavingTuple', $_GET)) {
            handleGroupByRequest();
        } else if (array_key_exists('viewAverage', $_GET)) {
            handleAverageRequest();
        } else if (array_key_exists('viewNestedTuple', $_GET)) {
            handleNestedRequest();
        } else if (array_key_exists('viewDivisionTuples', $_GET)) {
            handleDivisionRequest();
        }

        disconnectFromDB();
    }
}

if (isset($_POST['updateSubmit']) || isset($_POST['insertSubmit'])) {
    handlePOSTRequest();
} else if (isset($_GET['viewAllTupleRequest']) || isset($_GET['viewGroupByHavingTupleRequest'])
    || isset($_GET['viewAverageRequest']) || isset($_GET['viewNestedTupleRequest']) || isset($_GET['viewDivisionTupleRequest'])
) {
    handleGETRequest();
}
?>

<head>
    <title>Applicants</title>
</head>

<body>

    <h2>Add a New Applicant</h2>

    <form method="POST" action="applicants.php">
        <input type="hidden" id="insertQueryRequest" name="insertQueryRequest">
        Applicant Number: <input type="text" name="anumber"> <br /><br />
        Applicant Name: <input type="text" name="aname"> <br /><br />

        <label for="country">Nationality: </label>
        <select id="country" name="country" class="form-control">
            <option value="Afghanistan">Afghanistan</option>
            <option value="Åland Islands">Åland Islands</option>
            <option value="Albania">Albania</option>
            <option value="Algeria">Algeria</option>
            <option value="American Samoa">American Samoa</option>
            <option value="Andorra">Andorra</option>
            <option value="Angola">Angola</option>
            <option value="Anguilla">Anguilla</option>
            <option value="Antarctica">Antarctica</option>
            <option value="Antigua and Barbuda">Antigua and Barbuda</option>
            <option value="Argentina">Argentina</option>
            <option value="Armenia">Armenia</option>
            <option value="Aruba">Aruba</option>
            <option value="Australia">Australia</option>
            <option value="Austria">Austria</option>
            <option value="Azerbaijan">Azerbaijan</option>
            <option value="Bahamas">Bahamas</option>
            <option value="Bahrain">Bahrain</option>
            <option value="Bangladesh">Bangladesh</option>
            <option value="Barbados">Barbados</option>
            <option value="Belarus">Belarus</option>
            <option value="Belgium">Belgium</option>
            <option value="Belize">Belize</option>
            <option value="Benin">Benin</option>
            <option value="Bermuda">Bermuda</option>
            <option value="Bhutan">Bhutan</option>
            <option value="Bolivia">Bolivia</option>
            <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
            <option value="Botswana">Botswana</option>
            <option value="Bouvet Island">Bouvet Island</option>
            <option value="Brazil">Brazil</option>
            <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
            <option value="Brunei Darussalam">Brunei Darussalam</option>
            <option value="Bulgaria">Bulgaria</option>
            <option value="Burkina Faso">Burkina Faso</option>
            <option value="Burundi">Burundi</option>
            <option value="Cambodia">Cambodia</option>
            <option value="Cameroon">Cameroon</option>
            <option value="Canada">Canada</option>
            <option value="Cape Verde">Cape Verde</option>
            <option value="Cayman Islands">Cayman Islands</option>
            <option value="Central African Republic">Central African Republic</option>
            <option value="Chad">Chad</option>
            <option value="Chile">Chile</option>
            <option value="China">China</option>
            <option value="Christmas Island">Christmas Island</option>
            <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
            <option value="Colombia">Colombia</option>
            <option value="Comoros">Comoros</option>
            <option value="Congo">Congo</option>
            <option value="Congo, The Democratic Republic of The">Congo, The Democratic Republic of The</option>
            <option value="Cook Islands">Cook Islands</option>
            <option value="Costa Rica">Costa Rica</option>
            <option value="Cote D'ivoire">Cote D'ivoire</option>
            <option value="Croatia">Croatia</option>
            <option value="Cuba">Cuba</option>
            <option value="Cyprus">Cyprus</option>
            <option value="Czech Republic">Czech Republic</option>
            <option value="Denmark">Denmark</option>
            <option value="Djibouti">Djibouti</option>
            <option value="Dominica">Dominica</option>
            <option value="Dominican Republic">Dominican Republic</option>
            <option value="Ecuador">Ecuador</option>
            <option value="Egypt">Egypt</option>
            <option value="El Salvador">El Salvador</option>
            <option value="Equatorial Guinea">Equatorial Guinea</option>
            <option value="Eritrea">Eritrea</option>
            <option value="Estonia">Estonia</option>
            <option value="Ethiopia">Ethiopia</option>
            <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
            <option value="Faroe Islands">Faroe Islands</option>
            <option value="Fiji">Fiji</option>
            <option value="Finland">Finland</option>
            <option value="France">France</option>
            <option value="French Guiana">French Guiana</option>
            <option value="French Polynesia">French Polynesia</option>
            <option value="French Southern Territories">French Southern Territories</option>
            <option value="Gabon">Gabon</option>
            <option value="Gambia">Gambia</option>
            <option value="Georgia">Georgia</option>
            <option value="Germany">Germany</option>
            <option value="Ghana">Ghana</option>
            <option value="Gibraltar">Gibraltar</option>
            <option value="Greece">Greece</option>
            <option value="Greenland">Greenland</option>
            <option value="Grenada">Grenada</option>
            <option value="Guadeloupe">Guadeloupe</option>
            <option value="Guam">Guam</option>
            <option value="Guatemala">Guatemala</option>
            <option value="Guernsey">Guernsey</option>
            <option value="Guinea">Guinea</option>
            <option value="Guinea-bissau">Guinea-bissau</option>
            <option value="Guyana">Guyana</option>
            <option value="Haiti">Haiti</option>
            <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>
            <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
            <option value="Honduras">Honduras</option>
            <option value="Hong Kong">Hong Kong</option>
            <option value="Hungary">Hungary</option>
            <option value="Iceland">Iceland</option>
            <option value="India">India</option>
            <option value="Indonesia">Indonesia</option>
            <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>
            <option value="Iraq">Iraq</option>
            <option value="Ireland">Ireland</option>
            <option value="Isle of Man">Isle of Man</option>
            <option value="Israel">Israel</option>
            <option value="Italy">Italy</option>
            <option value="Jamaica">Jamaica</option>
            <option value="Japan">Japan</option>
            <option value="Jersey">Jersey</option>
            <option value="Jordan">Jordan</option>
            <option value="Kazakhstan">Kazakhstan</option>
            <option value="Kenya">Kenya</option>
            <option value="Kiribati">Kiribati</option>
            <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>
            <option value="Korea, Republic of">Korea, Republic of</option>
            <option value="Kuwait">Kuwait</option>
            <option value="Kyrgyzstan">Kyrgyzstan</option>
            <option value="Lao People's Democratic Republic">Lao People's Democratic Republic</option>
            <option value="Latvia">Latvia</option>
            <option value="Lebanon">Lebanon</option>
            <option value="Lesotho">Lesotho</option>
            <option value="Liberia">Liberia</option>
            <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
            <option value="Liechtenstein">Liechtenstein</option>
            <option value="Lithuania">Lithuania</option>
            <option value="Luxembourg">Luxembourg</option>
            <option value="Macao">Macao</option>
            <option value="Macedonia, The Former Yugoslav Republic of">Macedonia, The Former Yugoslav Republic of</option>
            <option value="Madagascar">Madagascar</option>
            <option value="Malawi">Malawi</option>
            <option value="Malaysia">Malaysia</option>
            <option value="Maldives">Maldives</option>
            <option value="Mali">Mali</option>
            <option value="Malta">Malta</option>
            <option value="Marshall Islands">Marshall Islands</option>
            <option value="Martinique">Martinique</option>
            <option value="Mauritania">Mauritania</option>
            <option value="Mauritius">Mauritius</option>
            <option value="Mayotte">Mayotte</option>
            <option value="Mexico">Mexico</option>
            <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
            <option value="Moldova, Republic of">Moldova, Republic of</option>
            <option value="Monaco">Monaco</option>
            <option value="Mongolia">Mongolia</option>
            <option value="Montenegro">Montenegro</option>
            <option value="Montserrat">Montserrat</option>
            <option value="Morocco">Morocco</option>
            <option value="Mozambique">Mozambique</option>
            <option value="Myanmar">Myanmar</option>
            <option value="Namibia">Namibia</option>
            <option value="Nauru">Nauru</option>
            <option value="Nepal">Nepal</option>
            <option value="Netherlands">Netherlands</option>
            <option value="Netherlands Antilles">Netherlands Antilles</option>
            <option value="New Caledonia">New Caledonia</option>
            <option value="New Zealand">New Zealand</option>
            <option value="Nicaragua">Nicaragua</option>
            <option value="Niger">Niger</option>
            <option value="Nigeria">Nigeria</option>
            <option value="Niue">Niue</option>
            <option value="Norfolk Island">Norfolk Island</option>
            <option value="Northern Mariana Islands">Northern Mariana Islands</option>
            <option value="Norway">Norway</option>
            <option value="Oman">Oman</option>
            <option value="Pakistan">Pakistan</option>
            <option value="Palau">Palau</option>
            <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>
            <option value="Panama">Panama</option>
            <option value="Papua New Guinea">Papua New Guinea</option>
            <option value="Paraguay">Paraguay</option>
            <option value="Peru">Peru</option>
            <option value="Philippines">Philippines</option>
            <option value="Pitcairn">Pitcairn</option>
            <option value="Poland">Poland</option>
            <option value="Portugal">Portugal</option>
            <option value="Puerto Rico">Puerto Rico</option>
            <option value="Qatar">Qatar</option>
            <option value="Reunion">Reunion</option>
            <option value="Romania">Romania</option>
            <option value="Russian Federation">Russian Federation</option>
            <option value="Rwanda">Rwanda</option>
            <option value="Saint Helena">Saint Helena</option>
            <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
            <option value="Saint Lucia">Saint Lucia</option>
            <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
            <option value="Saint Vincent and The Grenadines">Saint Vincent and The Grenadines</option>
            <option value="Samoa">Samoa</option>
            <option value="San Marino">San Marino</option>
            <option value="Sao Tome and Principe">Sao Tome and Principe</option>
            <option value="Saudi Arabia">Saudi Arabia</option>
            <option value="Senegal">Senegal</option>
            <option value="Serbia">Serbia</option>
            <option value="Seychelles">Seychelles</option>
            <option value="Sierra Leone">Sierra Leone</option>
            <option value="Singapore">Singapore</option>
            <option value="Slovakia">Slovakia</option>
            <option value="Slovenia">Slovenia</option>
            <option value="Solomon Islands">Solomon Islands</option>
            <option value="Somalia">Somalia</option>
            <option value="South Africa">South Africa</option>
            <option value="South Georgia and The South Sandwich Islands">South Georgia and The South Sandwich Islands</option>
            <option value="Spain">Spain</option>
            <option value="Sri Lanka">Sri Lanka</option>
            <option value="Sudan">Sudan</option>
            <option value="Suriname">Suriname</option>
            <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
            <option value="Swaziland">Swaziland</option>
            <option value="Sweden">Sweden</option>
            <option value="Switzerland">Switzerland</option>
            <option value="Syrian Arab Republic">Syrian Arab Republic</option>
            <option value="Taiwan">Taiwan</option>
            <option value="Tajikistan">Tajikistan</option>
            <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
            <option value="Thailand">Thailand</option>
            <option value="Timor-leste">Timor-leste</option>
            <option value="Togo">Togo</option>
            <option value="Tokelau">Tokelau</option>
            <option value="Tonga">Tonga</option>
            <option value="Trinidad and Tobago">Trinidad and Tobago</option>
            <option value="Tunisia">Tunisia</option>
            <option value="Turkey">Turkey</option>
            <option value="Turkmenistan">Turkmenistan</option>
            <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
            <option value="Tuvalu">Tuvalu</option>
            <option value="Uganda">Uganda</option>
            <option value="Ukraine">Ukraine</option>
            <option value="United Arab Emirates">United Arab Emirates</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="United States">United States</option>
            <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
            <option value="Uruguay">Uruguay</option>
            <option value="Uzbekistan">Uzbekistan</option>
            <option value="Vanuatu">Vanuatu</option>
            <option value="Venezuela">Venezuela</option>
            <option value="Viet Nam">Viet Nam</option>
            <option value="Virgin Islands, British">Virgin Islands, British</option>
            <option value="Virgin Islands, U.S.">Virgin Islands, U.S.</option>
            <option value="Wallis and Futuna">Wallis and Futuna</option>
            <option value="Western Sahara">Western Sahara</option>
            <option value="Yemen">Yemen</option>
            <option value="Zambia">Zambia</option>
            <option value="Zimbabwe">Zimbabwe</option>
        </select> <br /><br />

        Birthday (DD-MM-YYYY, i.e., 01-DEC-2000): <input type="text" name="abirthday"> <br /><br />

        <input type="submit" value="Add" name="insertSubmit"></p>
    </form>


    <hr />

    <h2>Update Name in Applicants</h2>
    <p>Please identify the applicants whose information you want to update by ApplicantID <em>(you can find all the ApplicantIDs in "View All Applicants" section).</em></p>

    <form method="POST" action="applicants.php">
        <!--refresh page when submitted-->

        <input type="hidden" id="updateQueryRequest" name="updateQueryRequest">
        ApplicantID: <input type="text" name="ApplicantID"> <br /><br />

        <select name="UpdateOptions" id="UpdateOptions">
            <option value="Update name">Update name</option>
            <option value="Update DateOfBirth">Update DateOfBirth</option>
            <option value="Update Nationality">Update Nationality</option>
        </select>

        New Value: <input type="text" name="newValue"> <br /><br />

        <input type="submit" value="Update" name="updateSubmit"></p>
    </form>

    <hr />

    <h2>View All Applicants </h2>
    <form method="GET" action="applicants.php">
        <!--refresh page when submitted-->
        <input type="hidden" id="viewAllTupleRequest" name="viewAllTupleRequest">
        <input type="submit" value="View" name="viewAllTuples"></p>
    </form>
    <?php echo $viewAllStatement ?>

    <hr />

    <h2>View average age of applicants from each country </h2>
    <form method="GET" action="applicants.php">
        <!--refresh page when submitted-->
        <input type="hidden" id="viewAverageRequest" name="viewAverageRequest">
        <input type="submit" value="View" name="viewAverage"></p>
    </form>
    <?php echo $viewAverageStatement ?>

    <hr />

    <h2>Find the country having applicants older than 50 years old</h2>
    <form method="GET" action="applicants.php">
        <!--refresh page when submitted-->
        <input type="hidden" id="viewGroupByHavingTupleRequest" name="viewGroupByHavingTupleRequest">
        <input type="submit" value="View" name="viewGroupByHavingTuple"></p>
    </form>
    <?php echo $viewGroupByStatement ?>

    <hr />

    <h2>Find the applicants average age in countries having more than 3 applicants</h2>
    <form method="GET" action="applicants.php">
        <!--refresh page when submitted-->
        <input type="hidden" id="viewNestedTupleRequest" name="viewNestedTupleRequest">
        <input type="submit" value="View" name="viewNestedTuple"></p>
    </form>
    <?php echo $viewNestedStatement ?>

    <hr />

    <h2>Find the applicants who have successfully applied for all 4 types of visa</h2>
    <form method="GET" action="applicants.php">
        <!--refresh page when submitted-->
        <input type="hidden" id="viewDivisionTupleRequest" name="viewDivisionTupleRequest">
        <input type="submit" value="View" name="viewDivisionTuples"></p>
    </form>
    <?php echo $viewDivisionStatement ?>

    <hr />

    <p>
        <a href="index.php">
            <button class="button button2">back</button>
        </a>
    </p>


</body>

</html>