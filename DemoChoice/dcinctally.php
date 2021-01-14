<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Incremental Tally script - updates a tally using old results + new vote
without recounting all of the ballots.  If this can't be done
(because there is a change in elimination order or a large accumulated
error in the surplus), the C++ version that counts all the ballots is called.
Assumes that dcconfigload has already been included
Mostly redundant with C++ version, which is more thoroughly commented.
Unique parts are those that trigger the C++ count.
Takes NuBallot and ValidBallot as inputs
*/

if (!isset($Seats)) $Seats=0;
if (!isset($Cands)) $Cands=0;
if (!isset($Expired)) $Expired=true;
if (!isset($ValidBallot)) $ValidBallot=false;

$cfile="dcctally"; // path to the C++ version
// $cfile="./dcctally"; // path to the C++ version
// should be something like "dcctally" for windows and "./dcctally" for Unix

$IncPrecision=0.005; // If the number of incremental votes tallied exceeds
// this fraction of the threshold, invoke a full recount

$Precision=0.000001; // If votes are within this amount, treat them as equal 
$PrecDigs=6;

// Dodge exhausted ballots during surplus transfers, and equalize winners
$Dodgex=true;
$DoEqualize=true;
$DoPile=false;

// HighRank returns the candidate at the current index position on a ballot
function HighRank(&$Ballot)
{
 global $Cands;
 if ($Ballot['Pos']==$Cands)
 { return $Cands; }
 else
 { return $Ballot[$Ballot['Pos']]; }
}

// GetPos returns the index position of a candidate on a ballot
function GetPos(&$Ballot,$whichcand)
{
 global $Cands;
 $ThePos=0;
  while ($ThePos<$Ballot['Ct'] && $Ballot[$ThePos]!=$whichcand)
   $ThePos++;
 if ($ThePos==$Ballot['Ct']) $ThePos=$Cands;
 return $ThePos;
}

function TallyBallot(&$TheBallot,$Surp,$xfer=false,$dodge=false)
// same as Ballot::tally() in C++ version
{
 global $Cands, $ElimFlag, $NewTally, $DoRecycle, $PrecDigs;
 $OldPos=$TheBallot['Pos'];
 $OldHR=HighRank($TheBallot);
 if ($xfer) $BalTalWt=$TheBallot['Wt'][$OldHR]*$Surp;
 else $BalTalWt=1.0;
 $LastPos=false;
 $Recycleto=$Cands;
 $TheBallot['Pos']=0;
 $hr=HighRank($TheBallot);

 while(!$LastPos)
 {
  if ($TheBallot['Pos']==$Cands) { break; }
  if ($TheBallot['Pos']==$TheBallot['Ct']-1) $LastPos=true;

  if ($ElimFlag[$hr]==0) break;
  else
  {
   if ($TheBallot['Pos']==$OldPos && $ElimFlag[$hr]==2) break;
   else
   {
    if ($DoRecycle && $Recycleto==$Cands && $ElimFlag[$hr]>0)
     $Recycleto=$TheBallot['Pos'];
    if ($LastPos)
    {
     if ($dodge && $ElimFlag[$OldHR]>0) $TheBallot['Pos']=$OldPos;
     else $TheBallot['Pos']=$Recycleto;
     $hr=HighRank($TheBallot);
     break;
    }
   } 
  } 
  $TheBallot['Pos']++;
  $hr=HighRank($TheBallot);
 }
 $NewTally[$hr]+=$BalTalWt;
 if (!isset($TheBallot['Wt'][$hr])) $TheBallot['Wt'][$hr]=0.0; 
 $TheBallot['Wt'][$hr]+=$BalTalWt;

 if ($xfer)
 {
  $NewTally[$OldHR]-=$BalTalWt;
  $TheBallot['Wt'][$OldHR]-=$BalTalWt;
  if (round($TheBallot['Wt'][$OldHR],$PrecDigs)==0) $TheBallot['Wt'][$OldHR]=NULL;
 }
 return $hr;
} 

function IncTally()
// Add new ballot to existing tally, if not expired
// see who it would count for, if expired
// Should be called before other functions using NewTally
{
 global $NewTally, $NuBal, $VoteMatrix, $RndNum, $MyOrder; 
 global $Expired, $MyTally, $Cands;

 // load old values
 $NewTally=$VoteMatrix[$RndNum];

 // tally new ballot
 $MyOrder[$RndNum]=TallyBallot($NuBal,1.0);

 for ($i=0; $i<=$Cands; $i++)
 { $MyTally[$i]=$NewTally[$i]-$VoteMatrix[$RndNum][$i]; }

 // remove tally of new vote if poll is expired
 if ($Expired) $NewTally=$VoteMatrix[$RndNum];
} 

function XferMyBallot($who)
// transfers the new ballot to its next choice, or at least see who it would count for
// identical to C++ version, except that old votematrix values are used and
// vote is recorded if poll is not expired.
{
 global $ElimFlag,$MyOrder,$DoMyXfer,$RndNum,$Surplus,$Dodgex,$MyTally;
 global $NewTally,$VoteMatrix,$NuBal,$Expired,$XferFromRnd,$Precision;
 global $Cands,$SurplusOld;

 $Surplus[$RndNum-1]=$SurplusOld[$RndNum-1];
 $DoDodge=($Dodgex && ($ElimFlag[$who]==1));

 // if vote is in the pile of a winner but is not recent, note this and don't xfer again
 // but reconsider if the winner is transferred from during equalization
 if ($who==HighRank($NuBal)) $DoMyXfer=true;
 else if ($ElimFlag[HighRank($NuBal)]==1) $DoMyXfer=false;
 // last case is needed when eliminating leftover losers at end

 if ($ElimFlag[$who]>0 && $XferFromRnd[$RndNum]>0 &&
     in_array($who,array_slice($MyOrder,0,$XferFromRnd[$RndNum]-1)))
  $DoMyXfer=false;
 $NewTally=$VoteMatrix[$RndNum];
 if (!$Expired)
 {
  for ($i=0; $i<=$Cands; $i++)
  { $NewTally[$i]+=$MyTally[$i]; }
 }
 $OldTally=$NewTally;

 if ($DoMyXfer)
 {
  if (isset($NuBal['Wt'][$who]) && $NuBal['Wt'][$who]>$Precision)
   $NuBal['Pos']=GetPos($NuBal,$who);
  $MyOrder[$RndNum]=TallyBallot($NuBal,$Surplus[$RndNum-1],true,$DoDodge);
  for ($i=0; $i<=$Cands; $i++)
  { $MyTally[$i]+=$NewTally[$i]-$OldTally[$i]; }
  if ($Expired) { $NewTally=$VoteMatrix[$RndNum]; }
 }
 else $MyOrder[$RndNum]=$MyOrder[$RndNum-1];
/*
 echo "testing - pardon the mess<br>";
 print_r($ElimFlag); echo "<br>";
 echo $RndNum.":".$who." "; print_r($NuBal['Wt']);
 echo "<br>";
*/
} 

function DoDepth()
// compute RankCt, MyDepth, MyTally
{
 global $Expired,$RankCt,$NuBal,$MyDepth,$Cands,$ElimFlag,$MyTally;

 if (!$Expired) $RankCt[$NuBal['Ct']-1]++;

 foreach ($NuBal['Wt'] as $i => $j)
 {
  $k=GetPos($NuBal,$i);
  if ($k!=$Cands && $ElimFlag[$NuBal[$k]]>0) $MyDepth[$k]+=$j;
  else $MyDepth[$Cands]+=$j;
 }
} 

function ResolveTies($Tier,$TieVotes,$W)
// Tries to break ties using successively previous rounds, then by lot
// W=1 for winner and -1 for loser
// Identical to C++ version
{
 global $Cands,$NewTally,$ElimFlag,$RndNum,$VoteMatrix,$BalCt,$Ties,$WonInRound;

 //Find ties in current tally
 $TieBreaker=0;
 $NumOfTies=0;
 for ($i=0; $i<$Cands; $i++)
 {
  if (($NewTally[$i]==$TieVotes) && ($Tier!=$i) &&
     ($ElimFlag[$i]==0 || $ElimFlag[$i]==2))
  {
   if (!($W==1 && $ElimFlag[$Tier]==2 && $ElimFlag[$i]==2 &&
         $WonInRound[$Tier]!=$WonInRound[$i]))
   {
    $NumOfTies++;
    $LTies[$NumOfTies-1]=$i;
   }
  } 
 } 
 $OrigNumOfTies=$NumOfTies;
 $LTies[$NumOfTies]=$Tier;

 //Break ties using previous round result
 $TieRnd=$RndNum;
 while(($TieRnd>0) && ($NumOfTies>0))
 {
  $TieRnd--;
  //Determine plurality winner/loser among tied cnds for this round
  $TieBreakVotes=$BalCt*((1-$W)/2);
  for ($i=0; $i<=$NumOfTies; $i++)
  {
   if (($W*$VoteMatrix[$TieRnd][$LTies[$i]]>$W*$TieBreakVotes))
  {
   $TieBreakVotes=$VoteMatrix[$TieRnd][$LTies[$i]];
   $TieBreaker=$i;
  } 
 } 

 $TieTemp=0;
 $OldTieBreaker=$LTies[$TieBreaker];
 for ($i=0; $i<=$NumOfTies; $i++)
 {
  if (($i!=$TieBreaker) && ($VoteMatrix[$TieRnd][$LTies[$i]]==$TieBreakVotes))
  {
   $TieTemp++;
   $LTies[$TieTemp-1]=$LTies[$i];
  } 
 } 

 $LTies[$TieTemp]=$OldTieBreaker;
 $NumOfTies=$TieTemp;
 } 

 //Break ties by lot if prev round method fails
 if ($NumOfTies>0)
 {
  $Lot=intval(($NumOfTies+1)*(mt_rand(0,10000000)/10000000));
  $Ties[$RndNum]=-1;
  return $LTies[$Lot];
 }
 else
 {
  if ($OrigNumOfTies>0) $Ties[$RndNum]=$TieRnd+1;
  else $Ties[$RndNum]=0;
  return $LTies[$TieBreaker];
 } 
} 

// ------------------------------------MAIN

// reset random number generator (for ties)
mt_srand((double)microtime()*1000000);

$DoIncTally=true;  // if false, do a full recount

// load previous tally output
$BalCt=0;

if (isset($Pollname) && isset($FilePath)) require("dctallyload.php");
if (!isset($TotalVotes)) $TotalVotes=0;
if (!isset($Surplus)) $Surplus=0;

$BalCt=$TotalVotes;
$SurplusOld=$Surplus;

if ($ValidBallot)
{
 // set up data structure for new ballot
 $NuBal=explode(",",$NuBallot); // a ballot's numerical index is its ranked list
 $NuBal['Ct']=count($NuBal); // how many ranks are listed
 $NuBal['Wt']=NULL; // stores ballot's weight in sub-array, indexed by candidate number
 $NuBal['Pos']=0; // current position in list

 // If the poll is expired, we will find out who the ballot would count for
 // but not actually count it, keeping track of the number of votes between full recounts
 if (!$Expired) { $BalCt++; $IncVotes++; }
}
else
// If there is no valid new ballot, do a full recount
{ $DoIncTally=false; echo "<!-- No valid ballot -->"; } 

// if there are no prior votes, let the C++ program set up arrays
if ($BalCt==0 || ($ValidBallot && !$Expired && $BalCt==1)) 
{ $DoIncTally=false; echo "<!-- No prior votes -->"; }

// For multi-winner polls, errors may occur in incremental tallies because each vote is
// tallied with a slightly different threshold.  To minimize this:
// Do a full recount upon receiving the first vote after expiration
// (no full recounts will occur after that, unless invoked without a valid ballot)
// Also do a full recount if the number of incremental votes since the last full recount
// exceeds a fraction (specified above) of the threshold
if (($Seats>1) && (($IncVotes>($IncPrecision*$Thresh)) || ($Expired && ($IncVotes>0))))
{ $DoIncTally=false; echo "<!-- Threshold discrepancy -->"; }

// for compatibility with polls that predate the
// dodge exhausted ballots feature - do a full recount
// so the tally does support the feature
if ($Dodgex && !isset($XferFromRnd) && isset($VoteMatrix))
{ $DoIncTally=false; echo "<!-- Pre-Dodgex -->"; }

// Initialize variables
if ($DoIncTally)
{
 for ($i=0; $i<$Cands; $i++)
 {
  // exclude appropriate candidates
  if ($Excl[$i]) $ElimFlag[$i]=-1;
   else $ElimFlag[$i]=0;

  // Clear variables associated with how the ballot will count
  $MyDepth[$i]=0;
  $MyTally[$i]=0;
 } 
 // Excl[Cands] is undefined, so just do others out of loop
 $ElimFlag[$Cands]=0;
 $MyTally[$Cands]=0;
 $MyDepth[$Cands]=0;
 $Elected=0;
 $RndNum=0;
 $DoMyXfer=true; // toggled by XferMyBallot
 $Thresh=$BalCt/($Seats+1); // Threshold w/o exhausted ballot correction
 $OldThresh=$Thresh; // First-round threshold is sent to file
} 

// ------------------------------------Main tally loop
while ($DoIncTally && $Elected<$Seats) 
{
 $ElimVal=0;

 // update number of remaining candidates
 $Remaining=0;
 for ($i=0; $i<$Cands; $i++)
 { if ($ElimFlag[$i]>=0) { $Remaining++; } }

// This part is different from C++ version

 if ($RndNum==0) IncTally();
 else XferMyBallot(abs($ElimOrder[$RndNum-1])-1);

 for ($i=0; $i<=$Cands; $i++)
 {
  $VoteMatrix[$RndNum][$i]=$NewTally[$i];
  if ($RndNum>0)
   $XferMatrix[$RndNum][$i]=$NewTally[$i]-$VoteMatrix[$RndNum-1][$i];
  else $XferMatrix[$RndNum][$i]=$NewTally[$i];
 } 

// The remainder, until the output section, is identical to the C++ version,
// with the exception of the if statement that detects reasons to do a full recount
 if ($Remaining<=$Seats)
 {
  //Declare remaining candidates elected
  $Elected=$Seats;
  for ($i=0; $i<$Cands; $i++)
  { if ($ElimFlag[$i]>=0) $ElimFlag[$i]=1; } 
 }
 else
 {
 //There are still candidates to eliminate

 $Thresh=$OldThresh*(1-$NoEx*$NewTally[$Cands]/$BalCt);

 //Determine plurality winner and loser for this tally
 $Loser=0;
 $Winner=0;
 $LoserVotes=$BalCt;
 $WinnerVotes=0;

 for ($i=0; $i<$Cands; $i++)
 {
  if (($NewTally[$i]<$LoserVotes) && ($ElimFlag[$i]==0))
  {
   $LoserVotes=$NewTally[$i];
   $Loser=$i;
  } 

  // plurality winner must properly handle those who have already won
  // if i have already won and the plurality winner so far hasn't, i beat PW.
  // if i have already won and so has P.W. but P.W. won later, i beat PW.
  // if i have more votes, and P.W. hasn't won yet, i beat PW.
  // if i have more votes, and PW has won, but not in an earlier round, i beat PW.
  if (($ElimFlag[$i]==2 &&
       ($ElimFlag[$Winner]==0 ||
        ($ElimFlag[$Winner]==2 && $WonInRound[$Winner]>$WonInRound[$i])
      )) ||
       (($NewTally[$i]>$WinnerVotes) &&
        ($ElimFlag[$i]==0 || $ElimFlag[$i]==2) &&
       !(($ElimFlag[$Winner]==2 && $WonInRound[$Winner]<$RndNum) &&
         ($ElimFlag[$i]==0 ||
          ($ElimFlag[$i]==2 && $WonInRound[$Winner]<$WonInRound[$i])
     )) ))
  {
   $WinnerVotes=$NewTally[$i];
   $Winner=$i;
  } 

  if ((round($NewTally[$i],$PrecDigs)>round($Thresh,$PrecDigs)) && ($ElimFlag[$i]==0))
  {
   $ElimFlag[$i]=2;
   $Elected++;
   $WonInRound[$i]=$RndNum;
  } 
 }

  $SurplusNum[$RndNum]=1.0;
  $SurplusDenom[$RndNum]=1.0;
  $Surplus[$RndNum]=1.0;

 if (round($WinnerVotes,$PrecDigs)>round($Thresh,$PrecDigs))
 {
  $Winner=ResolveTies($Winner,$WinnerVotes,1);
  if ($XferMatrix[$WonInRound[$Winner]][$Winner]>0)
  {
   $SurplusNum[$RndNum]=$WinnerVotes-$Thresh;
   $SurplusDenom[$RndNum]=$XferMatrix[$WonInRound[$Winner]][$Winner];
   $Surplus[$RndNum]=$SurplusNum[$RndNum]/$SurplusDenom[$RndNum];
  }
  $ElimFlag[$Winner]=1;
  $ElimVal=($Winner+1); 
 }
 else
 {
 //Nobody won
 $Loser=ResolveTies($Loser,$LoserVotes,-1);
 $ElimFlag[$Loser]=-1;
 $ElimVal=(-($Loser+1));
 } 
 //there was a winner
 } 
 //there are no more candidates to eliminate

 $ElimOrder[$RndNum]=$ElimVal;

 // If  change in elim order or # rounds during incremental tally, do full recount
 // exclude elim order changes involving candidates with zero votes
 // relies on lazy or operator (if 1st arg is true, second isn't evaluated)
 // Elim is defined in dctallyload and is the previous tally's ElimOrder
 if (($RndNum>=$Rnds) or ($Elim[$RndNum]!=$ElimOrder[$RndNum]) &&
     (($Elected<$Seats) &&
      !(($VoteMatrix[$RndNum][abs($ElimOrder[$RndNum])-1]==0)
       && ($VoteMatrix[$RndNum][abs($Elim[$RndNum])-1]==0))
    ))
   {
    $DoIncTally=false;
    echo "<!-- ";
    if ($RndNum>=$Rnds) echo "RndNum mismatch";
    else if ($Elim[$RndNum]!=$ElimOrder[$RndNum])
     { echo "Elim mismatch"; }
    echo " -->";
   }
 
 $RndNum++;
}

// Finish processing non-transferred winners
if ($DoIncTally)
{
 for ($i=0; $i<$Cands; $i++) if ($ElimFlag[$i]==2) $ElimFlag[$i]=1;
}

//----------------------------------recycle residual candidates
if ($DoIncTally && $DoRecycle && $RndNum>0)
{
 for ($i=0; $i<$Cands; $i++)
 {
  if ($ElimFlag[$i]==0)
  {
   $ElimFlag[$i]=-1;
   if ($VoteMatrix[$RndNum-1][$i]>0)
   {
    $SurplusNum[$RndNum-1]=1.0;
    $SurplusDenom[$RndNum-1]=1.0;
    $Surplus[$RndNum-1]=1.0;
    $ElimOrder[$RndNum-1]=-($i+1);
    XferMyBallot($i);
    for ($j=0; $j<=$Cands; $j++)
    {
     $VoteMatrix[$RndNum][$j]=$NewTally[$j];
     $XferMatrix[$RndNum][$j]=$NewTally[$j]-$VoteMatrix[$RndNum-1][$j];
    }
    $RndNum++;
   } // votematrix if
  } // elimflag if
 } // for loop
} // recycle residual candidates

if ($DoIncTally && $DoRecycle && $DoEqualize && $RndNum>0)
{
 $OldElimFlags=$ElimFlag;
 $MaxThresh=(($BalCt-$NewTally[$Cands])/$Seats);

 // initially all winners are eligible to receive votes
 for ($j=0; $j<$Cands; $j++)
  if ($ElimFlag[$j]>0) $ElimFlag[$j]=0;
 
 $Counter=$Seats;
 while ($Counter>0)
 {
  $Counter--;
  $TooMany=false;

  // identify winners with too many votes and who have not been transferred from yet
  // set TooMany flag if one is found 
  for ($j=0; $j<$Cands; $j++)
   if ($ElimFlag[$j]!=1 && round($NewTally[$j],$PrecDigs)>round($MaxThresh,$PrecDigs))
    { $ElimFlag[$j]=2; $TooMany=true; }

  if ($TooMany)
  {
   $Winner=0;
   $WinnerVotes=0;
   for ($j=0; $j<$Cands; $j++)
   {
    if ($ElimFlag[$j]==2 && $NewTally[$j]>$WinnerVotes)
    {
     $WinnerVotes=$NewTally[$j];
     $Winner=$j;
    }
   }
   $Winner=ResolveTies($Winner,$WinnerVotes,1);
   $ElimFlag[$Winner]=1;
   
   if (round($NewTally[$Winner],$PrecDigs)>round($MaxThresh,$PrecDigs)
       && $VoteMatrix[$RndNum-1][$Winner]>0)
   {
    $SurplusNum[$RndNum-1]=$NewTally[$Winner]-$MaxThresh;
    $SurplusDenom[$RndNum-1]=$NewTally[$Winner];
    $Surplus[$RndNum-1]=$SurplusNum[$RndNum-1]/$SurplusDenom[$RndNum-1];
    $ElimOrder[$RndNum-1]=($Winner+1);
    XferMyBallot($Winner);
    for ($j=0; $j<=$Cands; $j++)
    {
     $VoteMatrix[$RndNum][$j]=$NewTally[$j];
     $XferMatrix[$RndNum][$j]=$NewTally[$j]-$VoteMatrix[$RndNum-1][$j];
    }
    $RndNum++;
   }
  } // if toomany
 } // while counter>0
 $ElimFlag=$OldElimFlags;
} // equalize

if ($DoIncTally)
{
 $Rnds=$RndNum;
 DoDepth();
}

//---------------------------------Rewrite Tally File

if (!$Expired && $DoIncTally)
{
 $cr=chr(13).chr(10);

 // append the new ballot to the pile file, if used
 if ($DoPile)
 {
  $fp=fopen($FilePath.$Ballotfilename."_piles.txt","a+");
  $jstring="";
  for ($ii=0; $ii<$NuBal['Ct']; $ii++)
  {
   if (isset($NuBal['Wt'][$NuBal[$ii]]) &&
       $NuBal['Wt'][$NuBal[$ii]]>0)
    $jstring.=$NuBal[$ii].",".$NuBal['Wt'][$NuBal[$ii]].",";
  }
  fputs($fp,rtrim($jstring,",").$cr);
  fclose($fp);
 }

$fp=fopen($FilePath.$Ballotfilename."_tally.txt","w+");
fputs($fp,"Ballots   |".$BalCt.$cr);
fputs($fp,"IncVotes  |".$IncVotes.$cr);
$Line="Threshold |".$OldThresh;
if ($Hare==true)
{ $Line.="|Hare"; }
else
{ $Line.="|Droop"; } 
fputs($fp,$Line.$cr);

for ($j=0; $j<$Rnds; $j++)
{
 $TallyString="Tally     ";
 $XferString="Transfer  ";
 for ($i=0; $i<=$Cands; $i++)
 {
  $TallyString.="|".$VoteMatrix[$j][$i];
  $XferString.="|".$XferMatrix[$j][$i];
 } 
 $TallyString.="|".$ElimOrder[$j];
 $XferString=$XferString."|";
 fputs($fp,$XferString.$cr);
 fputs($fp,$TallyString.$cr);
} 

$StatusString="Status    ";
for ($i0=0; $i0<$Cands; $i0++)
{ $StatusString.="|".$ElimFlag[$i0]; } 
fputs($fp,$StatusString."|0|".$cr);

$StatusString="XferFrom  ";
for ($i0=0; $i0<$Rnds; $i0++)
{ $StatusString.="|".$XferFromRnd[$i0]; } 
fputs($fp,$StatusString."|0|".$cr);

$StatusString="Surplus   ";
for ($i0=0; $i0<$Rnds; $i0++)
{ $StatusString.="|".$Surplus[$i0]; } 
fputs($fp,$StatusString."|0|".$cr);

$DepthString="MyDepth   ";
for ($i0=0; $i0<$Cands; $i0++)
{
 $DepthString.="|".$MyDepth[$i0];
 if (($DoIncTally || $DoReset) && !$Expired)
  { $Depth[$i0]+=$MyDepth[$i0]; }
} 
fputs($fp,$DepthString."|0|".$cr);
$TallyString="MyTally   ";
for ($i0=0; $i0<=$Cands; $i0++)
{ $TallyString.="|".$MyTally[$i0]; }
fputs($fp,$TallyString."|".$cr);
 
// could add FracDepth here

$DepthString="Depth     ";
for ($i0=0; $i0<$Cands; $i0++)
{ $DepthString.="|".($Depth[$i0]); } 
fputs($fp,$DepthString."|".$VoteMatrix[$Rnds-1][$Cands]."|".$cr);

$DepthString="RankCt    ";
for ($i0=0; $i0<$Cands; $i0++)
{ $DepthString.="|".($RankCt[$i0]); } 
fputs($fp,$DepthString."|0|".$cr);

$DepthString="Ties      ";
for ($i0=0; $i0<$Rnds; $i0++)
{ $DepthString.="|".($Ties[$i0]); } 
fputs($fp,$DepthString."|0|".$cr);

fclose($fp);
} // if not (expired and doinctally)

if (!$DoIncTally && isset($Pollfile))
{
 echo "<!-- ";
 $CCall=$cfile." -q -f ".$Pollfile." -c ".$Cands." -s ".$Seats;
 if ($ExclCt>0) $CCall.=" -x ".$ExclStr;
 if (!$DoRecycle) $CCall.=" -r ";
 if ($DoPile) $CCall.=" -p ";
 if (!$DoEqualize) $CCall.=" -e ";
 if (!$Dodgex) $CCall.=" -d ";
 if (!$NoEx) $CCall.=" -n ";
 if ($ValidBallot) $CCall.=" -b ".$NuBallot;
 unset($Cout);
 exec($CCall,$Cout);
 echo $CCall." (".$Cout[0].")";
 if (count($Cout)>3 && $Cout[count($Cout)-3]=="MyBallot:")
 {
  $MyOrder=explode(",",$Cout[count($Cout)-2]);
  $MyTally=explode(",",$Cout[count($Cout)-1]);
 } else $ValidBallot=false;
 echo " -->";
}
?>
