<?php
  $folder = time();
  $Files = array($_FILES['fileToUpload']['name']);

  // Creating directory if not present.
  if (!file_exists('uploaded'))
  {
    mkdir('uploaded');
  }

  // Creating Table for displaying file names.
  print "<table border='1'>";
  print "<th>Sr.</th>";
  print "<th>File Names</th>";

  foreach ($_FILES['fileToUpload']['name'] as $x => $value)
  {
    $x = $x + 1;
    print "<tr>";
    print "<td>" . $x . "</td>";
    print "<td>" . $value . "</td>";
    print "</tr>";
  }

  // Accepting one or more files.
  $TotalCountOfFiles = count($_FILES['fileToUpload']['name']);
  for ($i = 0; $i < $TotalCountOfFiles; $i++)
  {

    // The temp file path is obtained;.
    $TmpFilePath = $_FILES['fileToUpload']['tmp_name'][$i];

    // A file path needs to be present.
    if ($TmpFilePath != "")
    {
      $NewFilePath = "uploaded/" . $folder . $_FILES['fileToUpload']['name'][$i];
      $Ext = pathinfo($NewFilePath, PATHINFO_EXTENSION);

      // Checking the file type.
      $FileExtension = array("html", "css", "txt", "php", "js");
      if (in_array($Ext, $FileExtension))
      {

        // Moving uploaded file to uploads folder, opening it reading file and accepting special characters.
        if (move_uploaded_file($TmpFilePath, $NewFilePath))
        {
          $MyFile = fopen($NewFilePath, "r")or die("Unable to open file!");
          $LineRead = file($NewFilePath);
          $FileString = implode(" ", $LineRead);
          $Special = htmlspecialchars($FileString);
          $FileString1 = explode("\r\n", $Special);

          // Explode for html tags with special characters.
          $ExplodeWithSC = explode("\r\n", $FileString);

          // Creating table for displaying error msg and line number.
          print "<table border='1'>";
          print "<tr>";
          print "<th colspan='3'>" . $_FILES['fileToUpload']['name'][$i] . "</th>";
          print "</tr>";
          print "<tr>";
          print "<th>Sr.</th>";
          print "<th>Line no</th>";
          print "<th>Error msg</th>";
          print "</tr>";

          $FileNames = array();
          foreach ($_FILES["fileToUpload"]["name"] as $Key => $FileName)
          {

            // Preventing user to use let,==,!= in JS file.
            if ($Ext == "js")
            {
              foreach ($FileString1 as $r => $String)
              {
                $r = $r + 1;
                $Check = array('let', '==', '!=', 'return 1', 'return 0');

                foreach ($Check as $StrToCheck)
                {
                  $varpos = strpos($String, $varis);
                  if (!empty($varpos) && $StrToCheck == 'let')
                  {
                    $FileNames[$FileName][] = array($r, "Please use var instead of let");
                  }
                  elseif (!empty($varpos) && $StrToCheck == '==')
                  {
                    $FileNames[$FileName][] = array($r, "Please use === instead of == ");
                  }
                  elseif (!empty($varpos) && $StrToCheck == '!=')
                  {
                    $FileNames[$FileName][] = array($r, "Please use !== instead of !=");
                  }
                  elseif (!empty($varpos) && $StrToCheck == 'return 1')
                  {
                    $FileNames[$FileName][] = array($r, "Please use True instead of 1");
                  }
                  elseif (!empty($varpos) && $StrToCheck == 'return 0')
                  {
                    $FileNames[$FileName][] = array($r, "Please use False instead of 0 ");
                  }
                }
              }
            }

            // Counting charcters on each line using string length function.
            foreach ($ExplodeWithSC as $y => $Line)
            {
              $SplitEachLine = str_split($Line);
              if ((count($SplitEachLine)) > 132)
              {
                $y = $y + 1;
                $FileNames[$FileName][] = array($y, "Line has exceed its limit of 132 ");
              }
            }

            // To check space after conditional and looping statements.
            $CheckPoints = array("if(", "else(", "elseif(", "for(", "foreach(", "while(");
            $WordsNotToUse = array('flag', 'echo');
            foreach ($FileString1 as $x1 => $val1)
            {
              $x1 = $x1 + 1;
              foreach ($CheckPoints as $Check)
              {
                if (strpos($val1, $Check) !== false)
                {
                  $FileNames[$FileName][] = array($x1, "Please use space after conditional or looping keyword ");
                }
              }

              // To restrict echo,flag keywords.
              foreach ($WordsNotToUse as $LineContent)
              {
                $Place = stripos($val1, $LineContent);
                if (!empty($Place) && ($LineContent == 'flag'))
                {
                  $FileNames[$FileName][] = array($x1, "You cannot use FLAG keyword");
                }
                elseif (!empty($Place) && $LineContent == 'echo')
                {
                  $FileNames[$FileName][] = array($x1, "Please use Print instead of Echo keyword");
                }
              }
            }

            // To check if text inside html tag are in lower case or not.
            $Pattern = "%<(/?[A-Z].*?)%";
            foreach ($ExplodeWithSC as $z => $Tag)
            {
              if (preg_match_all($Pattern, $Tag, $Matches1))
              {
                $z = $z + 1 ;
                $FileNames[$FileName][] = array($z, "Please use lower case characters inside html tags");
              }
            }

            // PHP open and close both are present or not.
            $CountsOpen = substr_count($FileString, "<?php");
            $CountsClose = substr_count($FileString, "?>");
            if ($CountsOpen !== $CountsClose)
            {
              $FileNames[$FileName][] = array("Current file", "Opening or closing tag missing");
            }

            // Full PHP tag is present or not.
            $Pattern1 = "%<\?[^p](.*)%";
            foreach ($ExplodeWithSC as $d => $phps)
            {
              if (preg_match_all($Pattern1, $phps, $Matches2))
              {
                $d = $d + 1;
                $FileNames[$FileName][] = array($d, "Full php tag needed");
              }
            }

            // Not allow continue and break keyword in php file.
            if ($Ext == "php")
            {
              foreach ($FileString1 as $s => $keywords)
              {
                $s = $s + 1;
                $Condition = array('break', 'continue');

                foreach ($Condition as $CheckCondition)
                {
                  $Place2 = strpos($keywords, $CheckCondition);
                  if (!empty($Place2)) 
                  {
                    $FileNames[$FileName][] = array($s, "You cannot use continue or break keyword if possible");
                  }
                }
              }
            }

            // Omit braces if only one line code after conditional statements.
            $MatchBraces = "/^if/i";
            foreach ($FileString1 as $b => $Braces)
            {
              if (preg_match_all($MatchBraces, $Braces, $FoundMatches))
              {
                $CheckVal = trim($FileString1[$b+1]);
                $Val = "{";
                $CheckVal1 = trim($FileString1[$b+3]);
                $Val1 = "}";
                if ($CheckVal == $Val && $CheckVal1 == $Val1 )
                {
                  $Open = $b+2;
                  $Close = $b+4;
                  $FileNames[$FileName][] = array($Open, "You can Remove opening brace");
                  $FileNames[$FileName][] = array($Close, "You can Remove closing brace");
                }
              }
            }
          }

          // Loop for retriving elements from array and creating row.
          foreach ($FileNames as $Key => $Value)
          {
            foreach ($Value as $Key1 => $Value1)
            {
              $Key1 = $Key1 + 1;
              print "<tr>";
              print "<td>" . $Key1 . "</td>";
              print "<td>" . $Value1[0] . "</td>";
              print "<td>" . $Value1[1] . "</td>";
              print "</tr>";
            }
              break;
          }

          // Closing the file.
          fclose ($MyFile);
          print '<script>alert("File uploaded Successfully")</script>';
        }
        else
        {
          print "Sorry, file not uploaded, please try again!";
        }
     }
     else
     {
       print "Sorry, only html,php,js and txt files are allowed.";
     }
    }
  }
?>
