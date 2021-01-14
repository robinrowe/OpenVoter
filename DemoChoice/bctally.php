<?php 
//DemoChoice: A preference voting package (C) 2001 Dave Robinson
//Incremental Tally script
//Assumes that dcconfigload has already been included
//Takes NuBallot and ValidBallot as inputs
$DoRecycle=false;

if (!isset($Seats)) $Seats=0;
if (!isset($Cands)) $Cands=0;
if (!isset($Expired)) $Expired=true;
if (!isset($ValidBallot)) $ValidBallot=false;

function HighRank(&$Ballot)
{
 global $Cands;
 if ($Ballot['Pos']==$Cands)
 { return $Cands; }
 else
 { return $Ballot[$Ballot['Pos']]; }
}

mt_srand((double)microtime()*1000000);
$BalCt=0;
$DoIncTally=true;
if (isset($NuBallot))
{
 $NuBal=explode(",",$NuBallot);
 $NuBal['Ct']=count($NuBal);
 $NuBal['Wt']=1.0;
 $NuBal['Pos']=0;
 $SavedBal=$NuBal;
 $Recycled=false;
}

if (isset($Pollname) && isset($FilePath)) require("dctallyload.php");
if (!isset($TotalVotes)) $TotalVotes=0;

$BalCt=$TotalVotes;
if ($BalCt==0 && $Expired) { $ValidBallot=false; }
if (($BalCt>0) || $ValidBallot)
{
 if ($ValidBallot)
 {
  if (!$Expired) { $BalCt++; $IncVotes++; }
 }
 else
 { $DoIncTally=false; } 

if (($Seats>1) && (($IncVotes>(0.002*$Thresh)) || ($Expired && ($IncVotes>0))))
 { $DoIncTally=false; }

function PollReset()
{
 global $Surplus, $ElimFlag, $DoIncTally, $MyDepth, $Depth, $MyTally, $WonInRound;
 global $BallotsLoaded, $Elected, $RndNum, $DoReset, $DoMyXfer, $Cands, $Expired;
 global $SavedBal,$NuBal, $Excl, $RankCt, $BalCt, $Remaining, $MultiFlag, $Ties;

 for ($i=0; $i<$Cands; $i++)
 {
  $Surplus[$i]=1;
  $ElimFlag[$i]=0;
  if ($Excl[$i]) { $ElimFlag[$i]=-1; }
  $MyDepth[$i]=0;
  if ((!$DoIncTally) || (!$Expired && ($BalCt==1))) 
   {$Depth[$i]=0; $RankCt[$i]=0; }
  $MyTally[$i]=0;
  $WonInRound[$i]=0;
  $Ties[$i]=0;
 } 
 $MyDepth[$Cands]=0;
 if ((!$DoIncTally) || (!$Expired && ($BalCt==1)))
  { $Depth[$Cands]=0; $RankCt[$Cands]=0; }
 $MyTally[$Cands]=0;
 $BallotsLoaded=false;
 $Elected=0;
 $RndNum=0;
 $DoReset=false;
 $DoMyXfer=true;
 $NuBal=$SavedBal;
} 

function BugReport()
{
 global $Surplus, $ElimFlag, $DoIncTally, $MyDepth, $Depth, $MyTally, $WonInRound, $MultiFlag;
 global $BallotsLoaded, $Elected, $RndNum, $DoReset, $DoMyXfer, $Cands;
 global $SavedBal,$NuBal, $Excl, $RankCt, $BalCt;
 global $Ballots,$PRecent,$PFirst,$PLast,$Remaining;

 echo "<table border><tr><td>PFirst</td><td>PLast</td><td>PRecent</td><td>Surplus</td>";
 echo "<td>ElimFlag</td><td>MyDepth</td><td>Depth</td><td>RankCt</td><td>MyTally</td>";
 echo "<td>WIR</td></tr>";
 for ($i=0; $i<$Cands; $i++)
 {
  echo "<tr><td>".$PFirst[$i]."</td><td>".$PLast[$i]."</td><td>".$PRecent[$i]."</td><td>";
  echo $Surplus[$i]."</td><td>";
  echo $ElimFlag[$i]."</td><td>";
  echo $MyDepth[$i]."</td><td>";
  echo $Depth[$i]."</td><td>";
  echo $RankCt[$i]."</td><td>";
  echo $MyTally[$i]."</td><td>";
  echo $WonInRound[$i]."</td></tr>";
 } 
  echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>";
  echo "&nbsp;</td><td>";
  echo "&nbsp;</td><td>";
  echo $MyDepth[$Cands]."</td><td>";
  echo $Depth[$Cands]."</td><td>";
  echo "&nbsp;</td><td>";
  echo $MyTally[$Cands]."</td><td>";
  echo "&nbsp;</td></tr></table>";

 echo "Elected ".$Elected."<br>";
 echo "Remaining ".$Remaining."<br>";
 echo "RndNum ".$RndNum."<p>";
} 

function LoadBallots()
{
 global $Ballots, $BalCt, $BallotsLoaded;
 global $FilePath, $Ballotname, $RankCt;

 //Read ballot data into array
 $Opened=!(($fp=fopen($FilePath.$Ballotname."_ballots.txt","r"))===false);
 $BalCt=0;
 while($Opened && !feof($fp))
 {
  $Ballots[$BalCt]=fgetcsv($fp,1000);
  $Ballots[$BalCt]['Ct']=count($Ballots[$BalCt]);
  $Ballots[$BalCt]['Wt']=1.0;
  $Ballots[$BalCt]['Pos']=0;
  $RankCt[$Ballots[$BalCt]['Ct']-1]++;
  $BalCt++;
 } 
 fclose($fp);
 $BallotsLoaded=true;
 $BalCt--; $RankCt[0]--; // Assumes ballot file is terminated by crlf
} 

function TallyBallot(&$TheBallot)
{
 global $Cands, $ElimFlag, $NewTally, $Surplus, $DoRecycle, $Recycled;
 $BalTalWt=$TheBallot['Wt'];
 $FoundIt=false;
 $Firstlap=true;
 $Recycled=false;
 while(!($FoundIt))
 {
  if ($TheBallot['Pos']==$Cands) { break; }
  $HiRank=HighRank($TheBallot);
  if ($TheBallot['Pos']==$TheBallot['Ct']-1) { $FoundIt=true; }

  if ($ElimFlag[$HiRank]==0)
  {
   $NewTally[$HiRank]+=$BalTalWt;
   $FoundIt=true;
  }
  else
  {
   if ($Firstlap && $ElimFlag[$HiRank]==2)
   { $FoundIt=true; }
   else
   {
    if ($Firstlap) { $BalTalWt*=$Surplus[$HiRank]; } 
    if ($FoundIt)
    {
     if ($DoRecycle)
     {
      $Repos=$TheBallot['Pos'];
      for ($Postemp=0; $Postemp<=$TheBallot['Pos']; $Postemp++)
      {
       if ($ElimFlag[$TheBallot[$Postemp]]>0)
       {
        $NewTally[$TheBallot[$Postemp]]+=$BalTalWt;
        $Repos=$Postemp;
        $Recycled=true;
        break;
       }
      }
      $TheBallot['Pos']=$Repos;
     }     
     if (!$Recycled)
     { 
      $NewTally[$Cands]+=$BalTalWt;
      $TheBallot['Pos']=$Cands;
     }
    }
   } 
  } 
  if ($FoundIt==false) { $TheBallot['Pos']++; } 
  $Firstlap=false;
 }
 $TheBallot['Wt']=$BalTalWt;
} 

function BuildList()
{
 global $NewTally, $PLast, $PFirst, $PRecent, $BalCt, $Cands, $Ballots;
 //Tallies ballots for a given set of eliminated candidates and surplus values
 
 for ($i=0; $i<=$Cands; $i++) { $NewTally[$i]=0; } 

 LoadBallots();
 for ($i=0; $i<$Cands; $i++)
 {
  $PLast[$i]=$BalCt;
  $PFirst[$i]=$BalCt;
 } 

 //Do Count 
 for ($i=0; $i<$BalCt; $i++)
 {
  TallyBallot($Ballots[$i]);
  $HiRank=HighRank($Ballots[$i]);
  if ($HiRank!=$Cands)
  {
   if ($PLast[$HiRank]==$BalCt)
   { $PFirst[$HiRank]=$i; }
   else
   { $Ballots[$PLast[$HiRank]]['Nx']=$i; } 
   $PLast[$HiRank]=$i;
   $Ballots[$PLast[$HiRank]]['Nx']=$BalCt; 
  }
 } 
 for ($i=0; $i<$Cands; $i++)
 { $PRecent[$i]=$PFirst[$i]; } 
} 

function IncTally()
{
 global $NewTally, $MyTally, $NuBal, $Cands, $VoteMatrix, $RndNum, $MyDepth, $MyOrder;
 global $RankCt,$DoIncTally,$Expired;
 //Tallies ballots for a given set of eliminated candidates and surplus values
 //This just adds the new vote to the existing tally

 if ($DoIncTally && !$Expired) { $RankCt[$NuBal['Ct']-1]++; }
 for ($i=0; $i<=$Cands; $i++) { $NewTally[$i]=0; } 
 TallyBallot($NuBal);
 for ($i=0; $i<=$Cands; $i++)
 {
  $MyTally[$i]=$NewTally[$i];
  $NewTally[$i]=$VoteMatrix[$RndNum][$i];
  if (!$Expired) { $NewTally[$i]+=$MyTally[$i]; }
 }
 $MyDepth[$NuBal['Pos']]=0;
 $MyOrder[$RndNum]=HighRank($NuBal);
} 

function XferBallots($who)
{
 global $NewTally,$VoteMatrix,$ElimFlag,$RndNum,$Cands,$PRecent,$PLast;
 global $BalCt,$Depth,$PFirst,$Ballots,$Recycled;

 for ($i=0; $i<$Cands; $i++)
 {
  $NewTally[$i]=$VoteMatrix[$RndNum-1][$i];
 } 
 $NewTally[$Cands]=$VoteMatrix[$RndNum-1][$Cands];
 $XferPtr=$PFirst[$who];
 $XferAll=true;

 while($XferPtr!=$BalCt)
 {
  if ($XferAll) 
  {
   $OldPos=$Ballots[$XferPtr]['Pos'];
   $OldWt=$Ballots[$XferPtr]['Wt'];
   TallyBallot($Ballots[$XferPtr]);
   $HiRank=HighRank($Ballots[$XferPtr]);
   if ($ElimFlag[$who]==1)
   { $Depth[$OldPos]+=$OldWt-$Ballots[$XferPtr]['Wt']; } 
   if ($Recycled)
   { $Depth[$Ballots[$XferPtr]['Pos']]+=$Ballots[$XferPtr]['Wt']; }
   $NewTally[$who]-=$Ballots[$XferPtr]['Wt'];
   $PFirst[$who]=$Ballots[$XferPtr]['Nx']; //Delete ballot from list
   //Add ballot to the new list, if not exhausted or recycled

   if (($HiRank<$Cands) && ($ElimFlag[$HiRank]==0))
   {
    if ($PLast[$HiRank]==$BalCt)
    { $PFirst[$HiRank]=$XferPtr; }
    else
    { $Ballots[$PLast[$HiRank]]['Nx']=$XferPtr; } 
    $PLast[$HiRank]=$XferPtr;
    $Ballots[$PLast[$HiRank]]['Nx']=$BalCt;
   } 
  }
  else
  {
   $Depth[$Ballots[$XferPtr]['Pos']]+=$Ballots[$XferPtr]['Wt'];
   $PFirst[$who]=$Ballots[$XferPtr]['Nx']; //Delete ballot from list
  }
  if ($XferPtr==$PRecent[$who]) { $XferAll=true; } 
  $XferPtr=$PFirst[$who];
 }
} 

function XferMyBallot($who)
{
 global $ElimFlag,$WonInRound,$MyOrder,$DoMyXfer,$MyDepth;
 global $NewTally,$VoteMatrix,$NuBal,$Expired;
 global $RndNum,$MyTally,$Cands,$DoIncTally,$Recycled;

/*
 if ($ElimFlag[$who]>0) {

 if ($WonInRound[$who]>0 and $MyOrder[$WonInRound[$who]-1]==$who)
 {
  $DoMyXfer=false;
  $MyDepth[$NuBal['Pos']]+=$NuBal['Wt'];
  $MyOrder[$RndNum]=HighRank($NuBal);
 }}
*/
 if (true)
 {
  for ($i=0; $i<=$Cands; $i++) { $NewTally[$i]=0; } 
  $OldPos=$NuBal['Pos'];
  $OldWt=$NuBal['Wt'];
  TallyBallot($NuBal);
  if ($NuBal['Pos']!=$OldPos) { $MyTally[$who]-=$NuBal['Wt']; } 
  for ($i=0; $i<=$Cands; $i++)
  {
   if ($NuBal['Pos']!=$OldPos)
   { $MyTally[$i]+=$NewTally[$i]; } 
   if ($DoIncTally)
   {
    $NewTally[$i]=$VoteMatrix[$RndNum][$i];
    if (!$Expired) { $NewTally[$i]+=$MyTally[$i]; }
   }
  } 
  if ($ElimFlag[$who]==1)
  { $MyDepth[$OldPos]+=$OldWt-$NuBal['Wt']; }
  $HiRank=HighRank($NuBal);
  if ($Recycled)
  { $MyDepth[$NuBal['Pos']]+=$NuBal['Wt']; }
  $MyOrder[$RndNum]=HighRank($NuBal);
 }
 else
 {
  if ($DoIncTally)
  {
   for ($i=0; $i<=$Cands; $i++)
   {
    $NewTally[$i]=$VoteMatrix[$RndNum][$i];
    if (!$Expired) { $NewTally[$i]+=$MyTally[$i]; }
   } 
  }
 }
} 

function DoDepth($who)
{
 global $ValidBallot,$NuBal,$MyDepth,$DoIncTally;
 global $PFirst,$BalCt,$Depth,$Ballots;
 global $Cands;

 if ($ValidBallot)
 {
  $MyHiRank=HighRank($NuBal);
  if ($who==$MyHiRank)
  { $MyDepth[$NuBal['Pos']]=$NuBal['Wt']; } 
 } 
 if (!$DoIncTally)
 {
  $XferPtr=$PFirst[$who];
  while($XferPtr!=$BalCt)
  {
   $Depth[$Ballots[$XferPtr]['Pos']]+=$Ballots[$XferPtr]['Wt'];
   $XferPtr=$Ballots[$XferPtr]['Nx'];
  } 
 } 
} 

function ResolveTies($Tier,$TieVotes,$W)
{
 global $Cands,$NewTally,$ElimFlag,$RndNum,$VoteMatrix,$BalCt,$Ties;

//Tries to break ties using successively previous rounds, then by lot
//W=1 for winner and -1 for loser

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
 $TieRnd=$TieRnd-1;
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
 if (($VoteMatrix[$TieRnd][$LTies[$i]]==$TieBreakVotes) && ($i!=$TieBreaker))
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
 if ($OrigNumOfTies>0)
 { $Ties[$RndNum]=$TieRnd+1; }
 else
 { $Ties[$RndNum]=0; }
 return $LTies[$TieBreaker];
} 
} 

// ------------------------------------Main tally loop

PollReset();
while ($Elected<$Seats) 
{
if ($DoReset) { PollReset(); } 
$ElimVal=0;
$Remaining=$Cands;
for ($i=0; $i<$Cands; $i++)
{ if (abs($ElimFlag[$i])>0) { $Remaining--; } }

if ($RndNum==0)
{
 if ($ValidBallot) { IncTally(); } 
 if (!$DoIncTally) { BuildList(); } 
 if ($Hare)
 { $Thresh=($BalCt/$Seats)-0.00000000001; }
  else
 { $Thresh=floor(1+$BalCt/($Seats+1)); } 
 $OldThresh=$Thresh;
 $NewTally[$Cands]=0;
}
else
{
 if ($ValidBallot) { XferMyBallot(abs($ElimOrder[$RndNum-1])-1); } 
 if (!$DoIncTally) { XferBallots(abs($ElimOrder[$RndNum-1])-1); }
} 

for ($i=0; $i<=$Cands; $i++)
{
 $VoteMatrix[$RndNum][$i]=$NewTally[$i];
 if ($RndNum>0)
 { $XferMatrix[$RndNum][$i]=$NewTally[$i]-$VoteMatrix[$RndNum-1][$i]; }
 else
 { $XferMatrix[$RndNum][$i]=$NewTally[$i]; } 
} 

if (($Remaining+$Elected)<=$Seats)
{
 //Declare remaining candidates elected
 $Elected=$Seats;
 for ($i9=0; $i9<$Cands; $i9++)
 {
  if ($ElimFlag[$i9]>=0)
  {
   $ElimFlag[$i9]=1;
   DoDepth($i9);
  } 
 } 
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

 if ((round($NewTally[$i],10)>=round($Thresh,10)) && 
     ($ElimFlag[$i]==0))
 {
  $ElimFlag[$i]=2;
  $Elected++;
  $Remaining--;
  $WonInRound[$i]=$RndNum;
 } 
} 

if (round($WinnerVotes,10)>=round($Thresh,10))
{
 $Winner=ResolveTies($Winner,$WinnerVotes,1);
 if ($XferMatrix[$WonInRound[$Winner]][$Winner]>0)
 {
  $Surplus[$Winner]=($WinnerVotes-$Thresh)/$WinnerVotes;
 }
 $ElimFlag[$Winner]=1;
 $ElimVal=($Winner+1);
 if ($Elected==$Seats) { DoDepth($Winner); } 
}
else
{
//Nobody won
$Loser=ResolveTies($Loser,$LoserVotes,-1);
$ElimFlag[$Loser]=-1;
$ElimVal=(-($Loser+1));
} 
//there was a winner

// the following BC-STV rule says that if there are only two candidates left.
// the one with the most votes wins, even if they are below the threshold.
// Prior to 28 Apr 09, $ElimFlag[$Otherguy]=1; it was changed to 2
// -- otherwise depth doesn't work.  Hopefully this didn't cause another bug (probably not)
 if ($ElimVal<0 && $Remaining==2 && $Elected==($Seats-1))
 {
  $Otherguy=$Cands;
  for ($i=0; $i<$Cands; $i++)
  {
   if ($ElimFlag[$i]==2) { $Otherguy=$Cands; break; }
   if ($ElimFlag[$i]==0 && $i!=$Loser) { $Otherguy=$i; } 
  }
  if ($Otherguy<$Cands)
  {
   $ElimFlag[$Otherguy]=2;
   $Elected++;
   $WonInRound[$Otherguy]=$RndNum;
   $ElimVal=$Otherguy+1;
  }
 }
} 
//there are no more candidates to eliminate

$ElimOrder[$RndNum]=$ElimVal;

// If  change in elim order or # rounds during incremental tally, do full recount
// exclude elim order changes involving candidates with zero votes
// relies on lazy or operator (if 1st arg is true, second isn't evaluated)
if ($DoIncTally && (
    ($RndNum>=$Rnds) or (
     ($Elim[$RndNum]!=$ElimOrder[$RndNum])) && (
      !(($VoteMatrix[$RndNum][abs($ElimOrder[$RndNum])-1]==0)
      && ($VoteMatrix[$RndNum][abs($Elim[$RndNum])-1]==0))
   )))
 {
  $DoIncTally=false;
  $DoReset=true;
 } 
// BugReport();
$RndNum++;
}

if ($DoRecycle)
{
 $DidRecycle=false;
 for ($i=0; $i<$Cands; $i++)
 {
  if ($ElimFlag[$i]==0)
  {
   $ElimFlag[$i]=-1;
   if ($VoteMatrix[$RndNum-1][$i]>0)
   {
    if (!$DidRecycle)
    {
     for ($j=0; $j<=$Cands; $j++)
     {
      $VoteMatrix[$RndNum][$j]=$VoteMatrix[$RndNum-1][$j];
      $XferMatrix[$RndNum][$j]=0;
     }
     $ElimOrder[$RndNum]=0;
     $RndNum++;
     $DidRecycle=true;
    }
    if ($ValidBallot) { XferMyBallot($i); } 
    if (!$DoIncTally) { XferBallots($i); }
    for ($j=0; $j<=$Cands; $j++)
    {
     $VoteMatrix[$RndNum][$j]=$NewTally[$j];
     $XferMatrix[$RndNum][$j]=$NewTally[$j]-$VoteMatrix[$RndNum-1][$j];
    }
    $ElimOrder[$RndNum-1]=-($i+1);
    $ElimOrder[$RndNum]=0;
    $RndNum++;
   }
  }
 }
}

$Rnds=$RndNum;

// Finish processing non-transferred winners
for ($i9=0; $i9<$Cands; $i9++)
{
 if ($ElimFlag[$i9]==2)
 {
  $ElimFlag[$i9]=1;
  DoDepth($i9);
 } 
} 

//---------------------------------Rewrite Tally File

if (!($Expired && $DoIncTally)) {
if (!$DoIncTally) { $IncVotes=0; }
$cr=chr(13).chr(10);
$fp=fopen($FilePath.$Ballotname."_tally.txt","w+");
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

$StatusString="Surplus   ";
for ($i0=0; $i0<$Rnds; $i0++)
// bctally keeps track of surplus by candidate,
// whereas dctally keeps track of surplus by round.
{ $StatusString.="|".$Surplus[abs($ElimOrder[$i0])-1]; } 
fputs($fp,$StatusString."|0|".$cr);

if ($ValidBallot)
{
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
} 

$DepthString="Depth     ";
for ($i0=0; $i0<$Cands; $i0++)
{ $DepthString.="|".($Depth[$i0]); } 
fputs($fp,$DepthString."|".$VoteMatrix[$Rnds-1][$Cands]."|".$cr);

$DepthString="RankCt    ";
for ($i0=0; $i0<$Cands; $i0++)
{ $DepthString.="|".($RankCt[$i0]); } 
fputs($fp,$DepthString."|0|".$cr);

$DepthString="Ties    ";
for ($i0=0; $i0<$Cands; $i0++)
{ $DepthString.="|".($Ties[$i0]); } 
fputs($fp,$DepthString."|0|".$cr);

fclose($fp);
} // if not (expired and doinctally)
} 
//BalCt > 0
?>
