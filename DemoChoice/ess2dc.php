<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
Converts old-style ES&S ballot image format to DemoChoice file format
You must pass a poll name and contest number (that of the first choice)
See DemoChoice-Readme.txt for licensing information
*/
$Pollname="DC";
$ThisFile="ess2dc.php";
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

$contestin=1;
if (array_key_exists("contest",$_GET))
{ $contestin=1*$_GET["contest"]; } 

 ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Polls</title>
</head>
<body bgcolor=white text=black style="font-family: arial;">
<?php
 echo $Pollname."<br>";;
 if (empty($Pollname)) { $Pollname="DC"; }

$Cands=0;
$Seats=1;

$WeirdLines=0;
$Cands=NULL;
$Precincts=NULL;
$TallyTypes=NULL;
$Title="";
$ContestID_Lookup=0;

$Cands[0]["ID"]=0;
$Cands[0]["Name"]="Undervote";
$Precincts[0]["ID"]=0;
$Precincts[0]["Name"]=0;
$TallyTypes[0]["ID"]=0;
$TallyTypes[0]["Name"]="None";



// read ballot image file
$OpenedImage=false;
if (file_exists($FilePath.$Pollfile."_image.txt"))
{
 $OpenedImage=!(($fpImg=fopen($FilePath.$Pollfile."_image.txt","r"))===false);
}
// echo $FilePath.$Pollfile."_image.txt<br>";

$j=0;
$Votes=NULL;

while($OpenedImage && !feof($fpImg) && $WeirdLines<5000000)
{
 $Line=trim(fgets($fpImg,1000));

 if (trim($Line)=="")
 {
  $WeirdLines++;
  // echo "Blank Line #".$WeirdLines."<br>";
  continue;
 }
 
 $LineNum = 1*substr($Line,0,4);
 if ($LineNum==0) continue;

 $Contest[1] = 1*trim(substr($Line,15,4));
 if ($Contest[1]!==$contestin) continue;
 $Contest[2] = 1*trim(substr($Line,24,4));
 $Contest[3] = 1*trim(substr($Line,33,4));
 if ($Contest[1]!=$Contest[2]-1 || $Contest[2]!=$Contest[3]-1)
 {
  echo "Ballot rejected: ".$Line."<br>";
  continue;
 }

 $Votes[$j][1] = 1*trim(substr($Line,20,2));
 $Votes[$j][2] = 1*trim(substr($Line,29,2));
 $Votes[$j][3] = 1*trim(substr($Line,38,2));

 if ($Votes[$j][1]!=0 || $Votes[$j][2]!=0 || $Votes[$j][3]!=0)
 {
  if ($j % 1000 == 0) echo $j."<br>";
  $j++;
 }
} // Ballot image loop
if ($OpenedImage) fclose($fpImg);
if ($OpenedImage) echo "Finished reading image file<br>";
else echo "Did not find image file<br>";

$OpenedCSV=false;
if ($OpenedImage)
{
 $OpenedCSV=!(($fpCSV=fopen($FilePath.$Pollfile."_csv.txt","w+"))===false);
}

if ($OpenedCSV)
{
 for ($i=0;$i<$j;$i++)
 {
  for ($k=1;$k<4;$k++)
  {
   fputs($fpCSV,$Votes[$i][$k]);
   
   if ($k==3)
   { 
    fputs($fpCSV,$cr);
   }
   else
   {
    fputs($fpCSV,",");
   }
  }
 }
}
if ($OpenedCSV) fclose($fpCSV);
if ($OpenedCSV) echo "Finished writing CSV file<br>";
else echo "Did not write CSV file<br>";
?>
 
</body>
</html>
