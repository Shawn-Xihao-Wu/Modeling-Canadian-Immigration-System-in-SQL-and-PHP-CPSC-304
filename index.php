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
    
    .button1 {background-color: #4CAF50; 
              width: 300px;
    } /* Green */
    .button2 {background-color: #008CBA; 
              width: 300px;
    } /* Blue */
    </style>

  <body>
    <div id="content">
      <p><a href="https://github.students.cs.ubc.ca/CPSC304-2022W-T1/project_f3a3g_o7c8d_t5h6p">Github</a></p>
      <h1>Manage Visas</h1>
      <p>
        <a href="oracle-test.php">
          <button class="button button2">See sample project</button>
        </a>
      </p>
      <p>
        <a href="issued-visa.html">
          <button class="button button2">Manage issued visas</button>
        </a>
      </p>
      <p>
        <a href="approved-institution.html">
          <button class="button button2">Manage approved institutions</button>
        </a>
  
      
      <h1>Manage People</h1>
      <p>
        <a href="issued-visa.html">
          <button class="button button2">Manage visa-applicants</button>
        </a>
      </p>
      <p>
        <a href="issued-visa.html">
          <button class="button button2">Manage visa-holders</button>
        </a>
      </p>


      <h1>Manage embassys</h1>
      <p>
        <a href="issued-visa.html">
          <button class="button button2">Manage embassys</button>
        </a>
      </p>
     
      <form action="/form-handler" method="POST">
        <p>Leave a comment here:</p>
        <textarea name="text-input"></textarea>
        <br/>
        <input type="submit" />
        
      </form>

    

    
    </body>

    </div>


    
</html>