<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
ballot file validator
*/
$ThisFile="ballot_file_validator.php";
require("dcconfigload.php");
$cr=chr(13).chr(10);

//Read ballot data into array
$Opened=!(($fp=fopen($FilePath.$Ballotfilename."_ballots.txt","r"))===false);
$OpenedOut=!(($fpout=fopen($FilePath.$Ballotfilename."_ballots_fixed.txt","w"))===false);

$BalCt=0;
while($Opened && $OpenedOut && !feof($fp))
{
 $BalCt++;
 $Ballot=fgets($fp,1000);
 $Balarray=explode(",",$Ballot);
 $Balarrayout=array();
 foreach ($Balarray as $b)
 {
  if ($b>=$Cands) echo $BalCt."(".$Ballot."): candidate too high<br>";
  else if ($b<0) echo $BalCt."(".$Ballot."): candidate too low<br>";
  else if (trim($b)=="")  echo $BalCt."(".$Ballot."): blank ranking<br>";
  else array_push($Balarrayout,$b);
 }
 $Balstring=trim(implode(",",$Balarrayout)).$cr;

 //echo $Balstring."<br>";
 if (count($Balarrayout)>0)
  fputs($fpout,$Balstring);
} 
fclose($fpout);
fclose($fp);

rename($FilePath.$Ballotfilename."_ballots.txt",$FilePath.$Ballotfilename."_ballots_broken.txt");
rename($FilePath.$Ballotfilename."_ballots_fixed.txt",$FilePath.$Ballotfilename."_ballots.txt");

echo "Done validating ".$Pollname."<br>";
?>
