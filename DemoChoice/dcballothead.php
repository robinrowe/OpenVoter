<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Javascript header for standard ballot
If you comment out the sorting, leaving duplicate dodging, remove the
 else break in vote_check 
*/ 
$BigBallot=50; // above this, truncate lists and skip unranked in sort
$hrs=getdate(time());
mt_srand($hrs["hours"]);
$ThisFile="dcballot.php";
require("dcconfigload.php");

if (array_key_exists("terse",$_GET))
{ $Verbose=false; }
else
{ $Verbose=true; } 

if (array_key_exists("bb",$_GET))
{ $barebones="true"; }
else
{ $barebones="false"; } 

if (array_key_exists("norot",$_GET))
{ $norot=true; }
else
{ $norot=false; }

$bbfile=$ThisFile;
if ((strpos($ThisFile,"?") ? strpos($ThisFile,"?")+1 : 0)!=0)
{ $bbfile.="&amp;bb=on"; } else { $bbfile.="?bb=on"; }

$InfoFile.="&amp;bt=ts";

$SkipCt=0;
for ($i=0; $i<$Cands; $i++)
{ if ($Excl[$i]) {$SkipCt++;} else {$Balrot[$i-$SkipCt]=$i; } } 
for ($i=0; $i<($Cands-$ExclCt); $i++)
{
 $temp=$Balrot[$i];
 $j=intval(($Cands-$ExclCt-$i)*(mt_rand(0,10000000)/10000000))+$i;
 if ($norot) { $j=$i; }
 $Balrot[$i]=$Balrot[$j];
 $Balrot[$j]=$temp;
 $invBalrot[$Balrot[$i]]=$i;
} 
$Cands2=$Cands-$ExclCt;
$ListLength=$Cands2;
if ($ListLength>$BigBallot) { $ListLength=$BigBallot; }
?>
<SCRIPT type="text/javascript" LANGUAGE="JavaScript"><!--
ns4dom=(document.layers)?1:0;
w3cdom=(document.getElementById)?1:0;
Cands=<?php echo $Cands2; ?>;
ListLength=<?php echo $ListLength; ?>;
oldmyval=0;
baddataflag=true;

function vote_check(theform)
{
 if (baddataflag)
 {
  hasarank=false;
  for (i0=0; i0<Cands; i0++)
  {
   if (w3cdom)
   {arank=document.getElementById("sel"+i0).selectedIndex;}
   else {arank=document.forms[0].elements[i0].selectedIndex;}
   if(arank>0) {hasarank=true;} else break;
   if (w3cdom)
   {document.getElementById("sel"+i0).selectedIndex=0;}
   else {document.forms[0].elements[i0].selectedIndex=0;}
  }
//  if (hasarank) {alert('Browser lost form data.  If you have not cast your vote, please try again.');}
  baddataflag=false;
 }
}

function testForEnter() 
{    
 if ((!<?php echo $barebones; ?>) && (w3cdom) && (event.keyCode == 13)) 
 {        
  event.cancelBubble = true;
  event.returnValue = false;
 }
} 

function newoldmyval(theval) { oldmyval=theval; }

function listsort(me,myval) {

// initialize arrays
var newstate = new Array(
<?php
 for ($i0=0; $i0<=$Cands2; $i0++)
{ 
 echo 0;
 if ($i0!=$Cands2) { echo chr(44); }
}
?> );
var changedit = new Array(
<?php
 for ($i0=0; $i0<=$Cands2; $i0++)
{ 
 echo 0;
 if ($i0!=$Cands2) { echo chr(44); }
}
?> );
var rankedit = new Array(
<?php
 for ($i0=0; $i0<=$ListLength; $i0++)
{ 
 echo 0;
 if ($i0!=$ListLength) { echo chr(44); }
}
?> );
changedit[me]=1;
baddataflag=false;

if ((!<?php echo $barebones; ?>) && (w3cdom || ns4dom)) {

// load form data
Lorank=0;
for (i0=0; i0<Cands; i0++)
{
 if (w3cdom) 
 {newstate[i0]=document.getElementById("sel"+i0).selectedIndex;}
 else {newstate[i0]=document.forms[0].elements[i0].selectedIndex;}
 rankedit[newstate[i0]]++;
 if(newstate[i0]>Lorank) {Lorank=newstate[i0];}
}

// determine button action
if (myval<0)
{
 if (w3cdom)  
 {oldmyval=document.getElementById("sel"+me).selectedIndex;}
 else {oldmyval=document.forms[0].elements[me].selectedIndex;}
 if (oldmyval!=0) { myval=0; }
 else if (Lorank==ListLength) { myval = Lorank; }
 else { Lorank++; myval=Lorank; }
 newstate[me]=myval;
 rankedit[oldmyval]--;
 rankedit[myval]++;
}

// process duplicate rankings 
if ((rankedit[myval]>1) && (myval!=0))
{
 // find first gap in rankings above and below myval 
 higap=myval;
 while((higap>0) && (rankedit[higap]>0)) { higap--; }
 logap=myval;
 while((logap<=ListLength) && (rankedit[logap]>0)) { logap++; }

 // lots of conditions in case browser trashes oldmyval somehow
 goup=false;
 godown=false;
 if (myval>oldmyval)
{ if (higap>0) { goup=true; } else if (logap<=ListLength) { godown=true; } }
 else if (myval<oldmyval) 
{ if (logap<=ListLength) { godown=true; } else if (higap>0) { godown=true; } }
 else 
{ if (higap>0) { goup=true; } else if (logap<=ListLength) { godown=true; } }

 if (goup)
 {
  for(i0=0; i0<Cands; i0++)
  {
   si=newstate[i0];
   if ((si>higap) && (si<=myval))
   { newstate[i0]--; changedit[i0]=1; }
  }
  newstate[me]=myval;
 }

 if (godown)
 { 
  for(i0=0; i0<Cands; i0++)
  {
   si=newstate[i0];
   if ((si<logap) && (si>=myval))
   {
    if (si!=ListLength)
    { newstate[i0]++; changedit[i0]=1; }
    else { newstate[i0]=0; changedit[i0]=1; }
   }
  }
  newstate[me]=myval;
 } 
} // end duplicate elimination

// remove gap if candidate is unranked
if (myval==0)
{
 higap=Lorank;
 while((higap>0) && (rankedit[higap]>0)) { higap--; }
 if (higap>0)
 {
  for(i0=0; i0<Cands; i0++)
  {
   if (newstate[i0]>higap) { newstate[i0]--; changedit[i0]=1; }
  }
 }
}

// bubble sort
if (w3cdom) {
 for(i0=1; i0<Cands; i0++)  
 { 
  Sortrank=newstate[i0];
  if (Sortrank==0) { Sortrank=Cands+1; }
  i1=i0;
  Tryrank=newstate[i1-1];
  if (Tryrank==0) { Tryrank=Cands+1; }
  unrankskip=0;
  movedit=false;
  while(Tryrank>Sortrank)
  {
   if (!movedit)
   {
    movedit=true;
    Sortname=document.getElementById("sel"+i0).getAttribute('name');
    Sorthtml=document.getElementById("label"+i0).innerHTML;
   }
   if (Tryrank==Cands+1)
   {
    // skip over unranked, if possible
    if ((Cands><?php echo $BigBallot; ?>) && (i1>1))
    {
     if (newstate[i1-2]==0)
     { i1--; unrankskip++; continue; }
    } 
    Tryrank=0;
   }
   Tryname=document.getElementById("sel"+(i1-1)).getAttribute('name');
   Tryhtml=document.getElementById("label"+(i1-1)).innerHTML;
   newstate[i1+unrankskip]=Tryrank; changedit[i1+unrankskip]=1;
   document.getElementById("sel"+(i1+unrankskip)).setAttribute('name',Tryname);
   document.getElementById("label"+(i1+unrankskip)).innerHTML=Tryhtml;
   i1--; unrankskip=0;
   if (i1<=0) { break; }
  Tryrank=newstate[i1-1];   
  if (Tryrank==0) { Tryrank=Cands+1; }
  }   
  if (Sortrank==Cands+1) { Sortrank=0; }
  if (movedit)
  {
   newstate[i1]=Sortrank; changedit[i1]=1;
   document.getElementById("sel"+i1).setAttribute('name',Sortname);
   document.getElementById("label"+i1).innerHTML=Sorthtml;
  }
 }
} // if w3cdom
notfoundyet=true;
for (i0=0; i0<Cands; i0++)
{
 if (changedit[i0]==1)
 {
  if (w3cdom) {document.getElementById("sel"+i0).selectedIndex=newstate[i0];}
  else {document.forms[0].elements[i0].selectedIndex=newstate[i0];}
 }
 if ((w3cdom) && (notfoundyet) && (newstate[i0]==myval))
 { document.getElementById("sel"+i0).focus(); notfoundyet=false; }
}
} // if either dom
oldmyval=myval;
}
//--></SCRIPT>
