<html>
<title>BandMates | Musician Feed</title>
    <!-- Algorithm for musicians for your band -->
<?php require_once('header.php'); ?>
<body>
<?php require_once('nav.php'); ?>
<section id="intro" class="clearfix">
    <div class="container"> 
        <?php
            session_start();
        
            include("config.php");

            if(isset($_SESSION['login_user']))
            {
                $seshUser = $_SESSION['login_user'];
                echo "<h1>These are the most compatible musicians for your Band </h1>";

                $sqlUserInfo = "SELECT * FROM Person WHERE username = '$seshUser'";
                $userInfoResult = mysqli_query($db, $sqlUserInfo) or die(mysqli_error($db));
                while ($rowD = mysqli_fetch_array($userInfoResult, MYSQL_ASSOC))
                {
                    $personID = $rowD['personID'];
                    $viewerAge = date_diff(date_create($rowD['dateOfBirth']), date_create('today'))->y;;
                }

                $sqlUsersBands = "SELECT * FROM Band WHERE bandID IN (SELECT bandID FROM BandMembers WHERE personID = '$personID')";  //select all bands
                $usersBandsResult = mysqli_query($db, $sqlUsersBands) or die(mysqli_error($db));
                $resultSize = mysqli_num_rows($usersBandsResult);
                
                if ($resultSize!=0){
                echo "
                <div>
                Pick one of your bands to see the best candidates available
                <form action='' method='post'>
                <select name ='selectedBand'>
                ";
                
                while ($rowC = mysqli_fetch_array($usersBandsResult, MYSQL_ASSOC))
                {
                    if ($rowC['bandID']==$_POST['selectedBand'])
                    {
                        echo "<option name='selectedBand' value='" . $rowC['bandID'] . "' selected>" . $rowC['bandName'] . "</option>";
                    }
                    else 
                    {
                        echo "<option name='selectedBand' value='" . $rowC['bandID'] . "'>" . $rowC['bandName'] . "</option>";
                    }
                }
                echo "
                </select>
                <input class='btn btn-success' type='submit' value='Submit'>
                </form>
                </div>";
                } else{
                    echo "
                <div>
                It doesn't look like you're in any bands!
                </div>";
                }
                    
                if(isset($_POST['selectedBand']))
                {
                    $selectedBandID = $_POST['selectedBand'];
                    $sqlSelectedBand = "SELECT * FROM Band WHERE bandID = $selectedBandID";
                    $selectedBandResult = mysqli_query($db, $sqlSelectedBand) or die(mysqli_error($db));
                    while ($rowE = mysqli_fetch_array($selectedBandResult, MYSQL_ASSOC))
                    {
                        $selectedBandName = $rowE['bandName'];
                    }

                    echo "Looking at candidates for " . $selectedBandName . "<br>";
                    

                    //Step 1: Get all users that play any instruments the band wants
                    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

                    $sqlUsersList = "SELECT * FROM Person WHERE personID IN (SELECT personID FROM Plays WHERE instrumentID IN (SELECT instrumentID FROM BandWants WHERE bandID = $selectedBandID)) AND personID != $personID;";
                    $usersListResult = mysqli_query($db, $sqlUsersList) or die(mysqli_error($db));
                    $userArray;
                    
                    $loopValA = 0;
                    while ($row = mysqli_fetch_array($usersListResult, MYSQL_ASSOC))
                    {
                        $userArray[$loopValA][0] = $row['personID'];
                        $userArray[$loopValA][1] = $row['firstName'];
                        $userArray[$loopValA][2] = $row['surName'];
                        $userArray[$loopValA][3] = $row['username'];
                        $userArray[$loopValA][4] = 0;
                        $userArray[$loopValA][5] = $row['lastLoginTime'];
                        $userArray[$loopValA][6] = date_diff(date_create($row['dateOfBirth']), date_create('today'))->y;
                        $userArray[$loopValA][7] = 0;
                        $userArray[$loopValA][8] = 0;
                        $userArray[$loopValA][9] = 0;
                        $userArray[$loopValA][10] = 0;
                        $loopValA++;
                    }
                            if ($loopValA==0){
                        echo "<br>";
                        echo "Oh no! Looks like there's no candidates for " . $selectedBandName;
                    }
                    //^ User Array Key:
                    //$userArray[][0] = PersonID
                    //$userArray[][1] = First Name
                    //$userArray[][2] = SurName
                    //$userArray[][3] = User Name
                    //$userArray[][4] = Total Score   
                    //$userArray[][5] = time of last login
                    //$userArray[][6] = age of user in years
                    //$userArray[][7] = difference in age of user in the array and the viewer
                    //$userArray[][8] = genreScore
                    //$userArray[][9] = recencyScore
                    //$userArray[][10] = ageScore


                
                    //Step 2: Genre score loop (from 0 to 100)
                    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

                    //Retrieve Number of Band Genres
                    $usersListResult = mysqli_query($db, $sqlUsersList) or die(mysqli_error($db));
                    $numBandGenres = "SELECT COUNT(genreID) FROM BandGenres WHERE bandID = $selectedBandID;";
                    $numBandGenreResult = mysqli_query($db, $numBandGenres) or die(mysqli_error($db));
                    $v3 = mysqli_fetch_array($numBandGenreResult, MYSQL_ASSOC);
                    $numBaGenres = $v3['COUNT(genreID)'];

                    $loopValB=0;
                    while ($rowB = mysqli_fetch_array($usersListResult, MYSQL_ASSOC))
                    {
                        //Retrieve Number of Player Genres
                        $numPlayerGenres = "SELECT COUNT(genreID) FROM LikedGenres WHERE personID = ".$rowB['personID'].";";
                        $numPlayerGenresResult = mysqli_query($db, $numPlayerGenres) or die(mysqli_error($db));
                        $v1 = mysqli_fetch_array($numPlayerGenresResult, MYSQL_ASSOC);
                        $numPlGenres = $v1['COUNT(genreID)'];
                        
                        //Retrieve Number of Matches
                        $numMatches = "SELECT COUNT(genreID) FROM BandGenres WHERE genreID IN (SELECT genreID FROM LikedGenres WHERE personID = ".$rowB['personID']." ) AND bandID = '$selectedBandID'";
                        $numMatchesResult = mysqli_query($db, $numMatches) or die(mysqli_error($db));
                        $v2 = mysqli_fetch_array($numMatchesResult, MYSQL_ASSOC);
                        $numMatches = $v2['COUNT(genreID)'];

                        $newBaGenres    = $numBaGenres * 100;       //multiply by 100
                        $newMatches     = $numMatches * 100;
                        $newPlGenres    = $numPlGenres * 100;

                        if($numPlGenres > $numBaGenres AND $numBaGenres>0)    //check for difference   if statement A
                        {
                            $temp = $numPlGenres  / $numBaGenres;
                            $newBaGenres = $newBaGenres * $temp;
                            $newMatches = $newMatches * $temp;
                            $newPlGenres = $newPlGenres;
                        }
                        else if($numBaGenres > $numPlGenres AND $numPlGenres>0)    //check for difference   if statement B
                        {
                            $temp = $numBaGenres / $numPlGenres;
                            $newPlGenres = $newPlGenres * $temp;
                            $newMatches = $newMatches * $temp;
                            $newBaGenres = $newBaGenres;
                        }
                        
                        $genreScore = ($newMatches / (($newPlGenres+$newBaGenres)-$newMatches))*100;

                        $userArray[$loopValB][8] = $genreScore;
                        
                        $loopValB++;
                    }
                    

                //Step 3: get login scores (based off recency of last login)
                //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

                if ($loopValA>0){
                    if ($loopValA==1)
                    { 
                        $fraction = 100/(count($userArray));
                    } 
                    else 
                    {
                        $fraction = 100/(count($userArray)-1);
                    }
                    for ($x = 0; $x < count($userArray); $x++)              //sort userArray by most recent date 
                    {
                        for ($y = $x+1; $y < count($userArray); $y++)
                        {
                            if ($userArray[$x][5]<$userArray[$y][5])
                            {
                                for ($z = 0; $z <= 10; $z++)     //move the data into the sorted spot for each user
                                {
                                    $temp = $userArray[$y][$z];
                                    $userArray[$y][$z] = $userArray[$x][$z];
                                    $userArray[$x][$z] = $temp;
                                }
                            }
                        }
                        $loginScore = (0 - ($fraction*$x)) + 100;

                        $userArray[$x][9] = $loginScore;
                    }
                }


                    //Step 4: get age scores (based off how close the age of the matches are to the viewer)
                    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

                    // $viewerAge = 25;     //commented out as it is now set via sql and session

                    if ($loopValA>0){
                    for ($x = 0; $x < count($userArray); $x++)  //get differences between userArray ages and viewerAge
                    {
                        if ($viewerAge > $userArray[$x][6])
                        {
                            $ageDifference = $viewerAge - $userArray[$x][6];
                        }
                        else if ($viewerAge < $userArray[$x][6])
                        {
                            $ageDifference = $userArray[$x][6] - $viewerAge;
                        }
                        else 
                        {
                            $ageDifference = 0;
                        }
                        $userArray[$x][7] = $ageDifference;
                    }
                    }
                    if ($loopValA>0){
                    //run sorting for age score distribution
                    for ($x = 0; $x < count($userArray); $x++)              //sort userArray by closest age
                    {
                        for ($y = $x+1; $y < count($userArray); $y++)
                        {
                            if ($userArray[$x][7]<$userArray[$y][7])
                            {
                                for ($z = 0; $z <= 10; $z++)     //move the data into the sorted spot for each user
                                {
                                    $temp = $userArray[$y][$z];
                                    $userArray[$y][$z] = $userArray[$x][$z];
                                    $userArray[$x][$z] = $temp;
                                }
                            }
                        }
                        $ageScore = (0 - ($fraction*$x)) + 100;

                        $userArray[$x][10] = $ageScore;
                    }
                    }


                //Step 5: Put the scores together and sort the users in order of best match to worst
                //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------         
                
                if ($loopValA>0){
                    for($a = 0; $a < count($userArray); $a++)
                    {
                        $gScore = $userArray[$a][8];
                        $lScore = $userArray[$a][9];
                        $aScore = $userArray[$a][10];
                        $tScore = $gScore + $lScore + $aScore;
                        $userArray[$a][4] = $tScore;
                    }    


                    for ($x = 0; $x < count($userArray); $x++)
                    {
                        for ($y = $x+1; $y < count($userArray); $y++)
                        {
                            if ($userArray[$x][4]<$userArray[$y][4])    //check if the current nested iteration of the array is lesser
                            {
                                for ($z = 0; $z <= 10; $z++)     //move the data into the sorted spot for each user
                                {
                                    $temp = $userArray[$y][$z];
                                    $userArray[$y][$z] = $userArray[$x][$z];
                                    $userArray[$x][$z] = $temp;
                                }
                            }
                        }
                    }

                    for($a = 0; $a < count($userArray); $a++)
                    {
                        echo "<br><div>";
                        if($a==0)
                        {
                            echo "<h3>BEST MATCH:</h3>";
                        }
                        else
                        {
                            echo "<h3>Match No. " . ($a+1) . ":</h3>";

                        }
                        echo "<div class='card text-white bg-primary mb-3' style='max-width: 24rem'>";
                        echo "<div class='card-header'>" . $userArray[$a][3]; "</div>" ;
                        echo "<br><br>";
                        echo "<div class='card-footer'><a class='btn btn-success' href='viewProfile.php?user=" . $userArray[$a][0] . "'>View Profile</a></div> ";
                        echo "</div></div>";
                        echo "<br><br>";
                    }    
                }
                }
            }  
            else        //if not logged in (session username not set)
            {
                echo "You must be logged in to see user matches for your band!";
            }

                
        ?>
                  </div>    
        </section>
        <?php require_once('footer.php'); ?>
    </body>
</html>  
