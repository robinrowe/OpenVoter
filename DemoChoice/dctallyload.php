<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Tally file loader
Note: this will choke if there are > 200 candidates because the fgets call
will truncate the array.  Just change the limit if this is a problem.
*/

$TotalVotes=0;
$IncVotes=0;
$Thresh=0;
$Rnds=0;
$Surplus=NULL;
$VoteMatrix=NULL;
for ($i=0; $i<$Cands+$Seats; $i++) { $Elim[$i]=0; $Ties[$i]=0; } 

function ParseTaggedLine(&$Arg)
{
 global $Cands;
 $Pipe=explode("|",$Arg);
 foreach ($Pipe as $Pipei => $Pipev)
 { $Pipe[$Pipei] = (float)$Pipe[$Pipei]; }
 $Arg=$Pipe[count($Pipe)-1];
 if (count($Pipe)>$Cands+1)
 { $Pipe=array_slice($Pipe,0,count($Pipe)-1); }
 return $Pipe; 
}

//Read #ballots, Threshold, Vote Matrix, Transfer Matrix and Status

$Opened=!(($fp=fopen($FilePath.$Ballotfilename."_tally.txt",'r'))===false);
$Pipe=0;
$WeirdLines=0;
while($Opened && !feof($fp))
{
 $Entry=fgets($fp,3000);
 if (!(($Pipe=strpos($Entry,"|"))===false))
 {
  $Tag=trim(substr($Entry,0,$Pipe));
  $Arg=trim(substr($Entry,$Pipe+1));
  switch ($Tag)
  {
   case "Ballots":
    $TotalVotes=1.0*$Arg;
   break;

   case "IncVotes":
    $IncVotes=1.0*$Arg;
   break;

   case "Threshold":
    $Pipe=explode("|",$Arg);
    $Thresh=1.0*$Pipe[0];
   break;

   case "Transfer":
    $Rnds++;
    $XferMatrix[$Rnds-1]=ParseTaggedLine($Arg);
   break;

   case "Tally":
    $VoteMatrix[$Rnds-1]=ParseTaggedLine($Arg);
    $Elim[$Rnds-1]=1.0*$Arg;
   break;

   case "Status":
    $Status=ParseTaggedLine($Arg);
   break;

   case "XferFrom":
    $XferFromRnd=ParseTaggedLine($Arg);
   break;

   case "Surplus":
    $Surplus=ParseTaggedLine($Arg);
   break;

   case "Depth":
    $Depth=ParseTaggedLine($Arg);
   break;

   case "RankCt":
    $RankCt=ParseTaggedLine($Arg);
   break;

   case "Ties":
    $Ties=ParseTaggedLine($Arg);
   break;

   default:
    $WeirdLines++;
    if ($WeirdLines>20) { $Opened=false; }
   break;
  } // switch
 } // Tag exists
} // end of file
fclose($fp);

?>
