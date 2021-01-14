<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
Converts Sequoia ballot image format to DemoChoice file format
NOTE: If a large number of contests are lumped into one giant image file and one lookup file,
use the ContestID parameter in the query string to select the desired contest.
See DemoChoice-Readme.txt for licensing information
*/
$Pollname="DC"; $Pollfile="DC";
$ThisFile="sequoia2dc.php";
$cr=chr(13).chr(10);

if (array_key_exists("SCRIPT_FILENAME",$_SERVER))
{ $FilePath=str_replace("\\\\","/",$_SERVER["SCRIPT_FILENAME"]); }
else
{ $FilePath=str_replace("\\\\","/",$_SERVER["PATH_TRANSLATED"]); }

if (!(strrpos($FilePath,"/")===false))
{ $FilePath=substr($FilePath,0,strrpos($FilePath,"/")+1)."data/"; }

$AbsURL=$_SERVER["SCRIPT_NAME"];
if (!(strrpos($AbsURL,"/")===false))
{ $AbsURL="http://".$_SERVER["SERVER_NAME"].substr($AbsURL,0,strrpos($AbsURL,"/")+1); }

if (array_key_exists("ContestID",$_GET)) $ContestID=1*$_GET["ContestID"];
else $ContestID = 0;

if (array_key_exists("poll",$_GET))
{ $Pollname=$_GET["poll"]; } 

$StrIn=substr($Pollname,0,40);
$LabelOut="";
$Slashcount=0;
$badlabel=false;
for ($i=1; $i<=strlen($StrIn); $i++)
{
 $Slashcount++;
 $jok=false;
 $j=ord(substr($StrIn,$i-1,1));
 if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
 if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
 if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
 if ($j==46 && $Slashcount>1) { $Slashcount=0; $jok=true; } 
 if ($jok) $LabelOut.=chr($j); else $badlabel=true;
} 
if ($badlabel || strlen($StrIn)==0) $LabelOut="DC";
$Pollname=$LabelOut;
$Pollfile=str_replace(".","/",$Pollname);

if (empty($Pollname)) 
{ $Pollname="DC"; $Pollfile="DC"; }

 ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Polls</title>
</head>
<body bgcolor=white text=black style="font-family: arial;">
<?php
 echo $Pollname." Contest ".$ContestID."<br>";;
 if (empty($Pollname)) { $Pollname="DC"; }

$Cands=0;
$Seats=1;

$WeirdLines=0;
$Cands=NULL;
$Precincts=NULL;
$TallyTypes=NULL;
$Title="";
$ContestID_Lookup=0;

$Cands[0]["ID"]=$ContestID;
$Cands[0]["Name"]="Undervote";
$Precincts[0]["ID"]=0;
$Precincts[0]["Name"]=0;
$TallyTypes[0]["ID"]=0;
$TallyTypes[0]["Name"]="None";

// read lookup file
$OpenedLookup=false;
if (file_exists($FilePath.$Pollfile."_lookup.txt"))
{
 $OpenedLookup=!(($fpLook=fopen($FilePath.$Pollfile."_lookup.txt","r"))===false);
}
while($OpenedLookup && !feof($fpLook))
{
 $Line=trim(fgets($fpLook,1000));
 if (trim($Line)=="") $WeirdLines++;

 $Record_Type = trim(substr($Line,0,10));
 $ID          = 1*substr($Line,10,7);
 $Description = trim(substr($Line,17,50));
 $List_Order  = 1*substr($Line,67,7);
 $Candidates_Contest_ID = 1*substr($Line,74,7);
 $Is_WriteIn  = 1*substr($Line,81,7);
 $Is_Provisional = 1*substr($Line,82,7);

 switch ($Record_Type)
 {
  case "Candidate":
   $Candtemp=NULL;
   $Candtemp["ID"]=$ID;
   $Candtemp["Name"]=ucwords(strtolower($Description));
   if ($ContestID==0 || $Candidates_Contest_ID==$ContestID)
   {
    array_push($Cands,$Candtemp);
    echo "Added candidate ".$Description."<br>";
   }
  break;

  case "Contest":
   if ($ContestID==0 || $ID==$ContestID)
   {
    $ContestID_Lookup=$ID;
    $Title=$Description;
    echo "Added title ".$Description."<br>";
   }
  break;

  case "Precinct":
   $Prectemp=NULL;
   $Prectemp["ID"]=$ID;
   $Prectemp["Name"]=$Description;
   array_push($Precincts,$Prectemp);
  break;

  case "Tally Type":
   $TTtemp=NULL;
   $TTtemp["ID"]=$ID;
   $TTtemp["Name"]=$Description;
   array_push($TallyTypes,$TTtemp);
   echo "Added tally type ".$Description."<br>";
  break;
 }

} // Lookup loop
if ($OpenedLookup) fclose($fpLook);

$Candtemp=NULL;
$Candtemp["ID"]=$ContestID;
$Candtemp["Name"]="Overvote";
array_push($Cands,$Candtemp);
$OV_ID = count($Cands)-1;

// write config file
$OpenedConfig=false;
if ($OpenedLookup)
{
 $OpenedConfig=!(($fpCfg=fopen($FilePath.$Pollfile."_config.txt","w+"))===false);
}

if ($OpenedConfig)
{
 fputs($fpCfg,"<TITLE> ".$Title.$cr);
 fputs($fpCfg,"<SEATS> 1".$cr);
 fputs($fpCfg,"<EXPIRE> 0".$cr);
 for ($j=0; $j<count($Cands); $j++)
  fputs($fpCfg,"<CANDIDATE> ".$Cands[$j]["Name"].$cr);
 fputs($fpCfg,"<EXCLUDE> 0,".$OV_ID.$cr);
}
if ($OpenedConfig) fclose($fpCfg);

if ($OpenedConfig) echo "Finished writing config file<br>";
else echo "Did not write config file<br>";

// write blank tally file
$OpenedTally=false;
if ($OpenedLookup && $OpenedConfig)
{
 $OpenedTally=!(($fpTally=fopen($FilePath.$Pollfile."_tally.txt","w+"))===false);
}

if ($OpenedTally)
{
 fputs($fpTally,"Ballots   |0".$cr);
}
if ($OpenedTally) fclose($fpTally);

if ($OpenedTally) echo "Finished writing blank tally file<br>";
else echo "Did not write tally file<br>";

// read ballot image file
$OpenedImage=false;
if ($OpenedLookup && $OpenedConfig && $OpenedTally && file_exists($FilePath.$Pollfile."_image.txt"))
{
 $OpenedImage=!(($fpImg=fopen($FilePath.$Pollfile."_image.txt","r"))===false);
}
$j=0;
$Votes=NULL;
$Votes[0]["ID"]=-1;

while($OpenedImage && !feof($fpImg) && $WeirdLines<50)
{
 $Line=trim(fgets($fpImg,1000));
 if (trim($Line)=="")
 {
  $WeirdLines++;
  continue;
 }
 
 $Contest_ID    = 1*substr($Line,0,7);
 $Pref_Voter_ID = 1*substr($Line,7,9);
 $Serial_Number = 1*substr($Line,16,7);
 $Tally_Type_ID = 1*substr($Line,23,3);
 $Precinct_ID   = 1*substr($Line,26,7);
 $Vote_Rank     = 1*substr($Line,33,3);
 $Candidate_ID  = 1*substr($Line,36,7);
 $Over_Vote     = 1*substr($Line,43,1);
 $Under_Vote    = 1*substr($Line,44,1);

 if ($Contest_ID != $ContestID_Lookup) continue;

 if ($Pref_Voter_ID!=$Votes[$j]["ID"])
 {
  // print_r($Votes[$j]);
  // echo "<br>";
  if ($j % 1000 == 0) echo $j."<br>";

  $j++;
 }
 
 $Votes[$j]["ID"] = $Pref_Voter_ID;
 
 $Votes[$j]["TallyType"] = "None";
 for ($k=0;$k<count($TallyTypes);$k++)
 {
  if ($TallyTypes[$k]["ID"]==$Tally_Type_ID)
  {
   $Votes[$j]["TallyType"] = $TallyTypes[$k]["Name"];
   continue;
  }
 }

 $Votes[$j]["Precinct"] = 0;
 for ($k=0;$k<count($Precincts);$k++)
 {
  if ($Precincts[$k]["ID"]==$Precinct_ID)
  {
   $Votes[$j]["Precinct"] = $Precincts[$k]["Name"];
   continue;
  }
 }

 if ($Over_Vote==1)
  $Votes[$j][$Vote_Rank] = $OV_ID;
 else if ($Under_Vote==1)
  $Votes[$j][$Vote_Rank] = 0;
 else
 {
  $Votes[$j][$Vote_Rank] = 0;
  for ($k=0;$k<count($Cands);$k++)
  {
   if ($Cands[$k]["ID"]==$Candidate_ID)
   {
    $Votes[$j][$Vote_Rank] = $k;
    continue;
   }
  }
 }
} // Ballot image loop
if ($OpenedImage) fclose($fpImg);
$j++;
if ($OpenedImage) echo "Finished reading image file<br>";
else echo "Did not find image file<br>";

// write CSV file
$OpenedCSV=false;
if ($OpenedLookup && $OpenedConfig && $OpenedTally && $OpenedImage)
{
 $OpenedCSV=!(($fpCSV=fopen($FilePath.$Pollfile."_csv.txt","w+"))===false);
}

if ($OpenedCSV)
{
 fputs($fpCSV,"VoterID,TallyType,Precinct,1st,2nd,3rd".$cr);
 for ($i=1;$i<$j;$i++)
 {
  fputs($fpCSV,$Votes[$i]["ID"].",".$Votes[$i]["TallyType"].",".$Votes[$i]["Precinct"].",");
  $BalDepth=count($Votes[$i])-3+1;
  for ($k=1;$k<$BalDepth;$k++)
  {
   fputs($fpCSV,$Votes[$i][$k]);
   if ($k==$BalDepth-1)
    fputs($fpCSV,$cr);
   else
    fputs($fpCSV,",");
  }
 }
}
if ($OpenedCSV) fclose($fpCSV);

// write DC ballot file
// DemoChoice doesn't know how to skip candidates below an overvote, so replace with undervote
$OpenedBallots=false;
if ($OpenedLookup && $OpenedConfig && $OpenedTally && $OpenedImage && $OpenedCSV)
{
 $OpenedBallots=!(($fpBal=fopen($FilePath.$Pollfile."_ballots.txt","w+"))===false);
}

if ($OpenedBallots)
{
 for ($i=1;$i<$j;$i++)
 {
  $BalDepth=count($Votes[$i])-3+1;
  $OV=false;
  for ($k=1;$k<$BalDepth;$k++)
  {
   if ($OV) 
    fputs($fpBal,"0");
   else
    fputs($fpBal,$Votes[$i][$k]);
   if ($Votes[$i][$k]==$OV_ID) $OV=true;
   if ($k==$BalDepth-1)
    fputs($fpBal,$cr);
   else
    fputs($fpBal,",");
  }
 }
}
if ($OpenedBallots) fclose($fpBal);

$CCall="./dcctally -r -s 1 -f ".$Pollfile." -c ".count($Cands)." -x 0,".(count($Cands)-1);
unset($Cout);
echo $CCall."<br>";
exec($CCall,$Cout);
?>
<a href="dcresults.php?poll=<?php echo $Pollname; ?>">Results</a>
 
</body>
</html>
