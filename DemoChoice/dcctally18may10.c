/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information

Compiling instructions: use the GCC compiler and use the command
 g++ dcctally18may10.c -o dcctally -O2 -s
GCC is built into Unix-like operating systems, including Mac OS X if you've enabled the developer package
For Windows, use MinGW (http://www.mingw.org).  The cygwin version is less desirable.
Feel free to use other compilers if that works better for you.
Be sure to check DEFAULT_PATH below.

For use instructions, enter dcctally -h at the command line

the "Hare" threshold in the original version of DemoChoice is not implemented here.
TODO: defragment ballots, add fracdepth to inctally
change log since 31aug version:
Revised determination of remaining candidates
18 may 2010: removed scope operators in constructors, which generated an error after a server 
upgrade.
*/
#include <iostream>
#include <fstream>
#include <vector>
#include <cstring>
#include <ctime>
#include <cstdlib>
#include <cmath>
#define DEFAULT_PATH "./data/"
// should be "./data/" for unix or "data\\" for windows
// if it's neither of these, update help text accordingly

#define DEFAULT_FILE "DC"
// that is, look in ./data/DC_ballots.txt
// save to DC_tally.txt and optionally DC_piles.txt

#define THISFILE "dcctally20sep05.c"
#define PRECISION 0.000001 // numbers closer than this are considered equal
using namespace std;

// these can be toggled or changed by command-line -d -n -r -e -p -c <num> -s <num>
bool Dodgex=1;     // Dodge exhausted ballots during surplus transfers
bool NoEx=1;       // Remove exhausted ballots from threshold calculation
bool DoRecycle=1;  // Recycle exhausted ballots (and those of residual candidates) to highest-ranked winner
bool DoEqualize=1; // Equalize number of votes of remaining candidates (must also recycle)
bool DoPile=0;     // Dump file listing who each vote counts for and how much
int Cands=2;       // number of candidates
int Seats=1;       // number of seats

bool Recycled=0;    // latches to 1 if transfers occur after all winners are determined
bool ValidBallot=0; // set to 1 if a ballot was added by -b on command line
bool DoMyXfer=1;    // switched on and off by xfermyballot function
int RndNum=0;       // number of the current round, starting from zero
long BalCt=0;       // number of ballots

// This program relies heavily on the vector template from the C++ standard
// template library, allowing it to handle memory allocation for arrays.
// Beyond that and the use of simple objects, the program doesn't use fancy 
// C++ features and could have been written in C.

vector<int> ElimFlag;        // current status of each candidate
vector<int> ElimOrder;       // who won/lost in each round
// 0 if continuing, -1 if eliminated, 1 if elected, 2 if elected but not yet transferred
// ElimOrder will be zero if no one is marked for transfer in a given round
// e.g. if the number of candidates equals the number of seats

vector<int> WonInRound;      // round in which a candidate won
vector<int> Ties;            // 0 if no ties, -1 if a tie was broken by lot
// otherwise the round number, starting from 1, in which a tie was resolved 

vector<int> XferFromRnd;     
// For each round, 0 if no winner, otherwise the round (starting from 1) before which
// ballots are not subject to transfer

vector<long> FracDepth;      // Number of ballots that counted for a given number of winners 
vector<long> RankCt;         // Number of ballots that ranked a given number of candidates
vector<double> Surplus;      // For each round, the surplus fraction used for transfers
vector<double> NewTally;     // array used to sum changes in votes between rounds
vector<double> OldTally;     // temporarily stores NewTally when processing command line ballot
vector<double> SurplusNum;   // numerator of surplus
vector<double> SurplusDenom; // denominator of surplus
vector<double> Depth;        // Sum of weights from ballots that counted for a winner in each ballot position
vector<double> MyDepth;      // depth for the ballot on the command line (if any)
vector<double> MyTally;      // who the ballot on the command line counted for
vector<int> MyOrder;         // who the command line ballot counted for in a given round
// (neglecting anyone it may have been transferred from)

vector< vector<double> > VoteMatrix; // 2D array of the tally by [round][candidate]
vector< vector<double> > XferMatrix; // 2D array of the difference between two rounds
// The last entry in [candidate] arrays holds the number of exhausted ballots.
// XferMatrix is redundant but is sometimes convenient and may be useful in any
// future implementation of multiple eliminations per round (though this can also be
// achieved by lumping together rounds in subsequent processing of the results)

double round(double val)
// just cleans up some floating point messes at certain points
{
 if (val<PRECISION && val>-PRECISION) return 0.0; else return val; 
}

class whofor
// an array element associated with a ballot describing who it counts for
{
 public:
 char who;      // which candidate it counts for
 long next;     // array element of next ballot in this candidate's pile
 double weight; // fraction of this vote counted toward the candidate

 whofor()
 // set default values
 {
  whofor::who=0;
  whofor::next=0;
  whofor::weight=1.0;
 }
} tempwho;

class Ballot
{
 public:
 vector<char> ranklist; // ranked candidates as described in input file, split into array
 // the use of char to represent candidates saves memory but limits # candidates to 255

 char pos;              // index of who in ranklist ballot counts for (not counting previous winners)
 // equals the number of candidates if the ballot is exhausted

 vector<whofor> wt;     // array describing who ballot counts for, and how much for each

 Ballot()
// initialize a ballot
{
 Ballot::ranklist.clear();
 Ballot::wt.clear();
 Ballot::pos=0;
}

int load(char *inbuf)
// loads a line (string) from the input file into the ranklist array
// for speed, doesn't flag invalid ballots but just crops between 0 and Cands
{
 int r;
 char* rankstr;

 Ballot::ranklist.clear();
 rankstr = strtok(inbuf,","); // standard C function that pulls off first ranking
 while (rankstr != NULL)
 {
  r=abs(atoi(rankstr));       // convert to integer and eliminate sign
  if (r<Cands)                // skip if weird number (too high)
  Ballot::ranklist.push_back((char)r); // convert to char and add to list
  rankstr = strtok (NULL,","); // get next ranking
 }
 Ballot::wt.clear();
 Ballot::pos=0;
 return Ballot::ranklist.size();
}

 char GetPos(char whichcand)
 // finds the position of a candidate in a ballot's list
 // returns the number of candidates if not found
 // (a valid result is less than this)
 {
  char ThePos=0;
   while (ThePos<Ballot::ranklist.size() && 
   Ballot::ranklist[ThePos]!=whichcand)
    ThePos++;
  if (ThePos==Ballot::ranklist.size()) ThePos=Cands;
  return ThePos;
 }

 char getwho(char whichcand)
 // finds the position in the whofor array of a given candidate
 // returns the size of the array if not found
 // (a valid result is less than this)
 {
  char ThePos=0;
  while (ThePos<Ballot::wt.size() &&
  Ballot::wt[ThePos].who!=whichcand)
   ThePos++;
  return ThePos;
 } 

 long getnext(char whichcand)
 // returns the index of the next ballot in a given candidate's pile
 // returns number of ballots if not found
 // Caution: this value is garbage prior to loading all ballots and building list
 // (a valid result is less than this, or equal if it's the last in the pile)
 {
  int index=Ballot::getwho(whichcand);
  if (index==Ballot::wt.size()) return BalCt;
  else return Ballot::wt[index].next;
 }

 void setnext(char whichcand, long whichbal)
 // Call this only on the last ballot in a pile.
 // adds a given ballot to the end of the pile
 // by having the last ballot point to it.
 // If the last ballot doesn't already count for the candidate -
 // which should only happen if this is called while loading ballots -
 // a new entry for the candidate is created in this ballot's who-for array.
 {
  int index=Ballot::getwho(whichcand);
  if (index==Ballot::wt.size())
  {
   tempwho.who=whichcand;
   tempwho.weight=1.0;
   tempwho.next=whichbal;
   Ballot::wt.push_back(tempwho);
  }
  else
   Ballot::wt[index].next=whichbal;
 }

 double getweight(char whichcand)
 // returns the fraction of a vote that a ballot counts for a given candidate
 // after finding the candidate in the ballot's who-for array
 // returns 0 if the candidate is not found in the array
 {
  int index=Ballot::getwho(whichcand);
  if (index==Ballot::wt.size()) return 0.0;
  else return Ballot::wt[index].weight;
 }

 void addweight(char whichcand, double thewt)
 // adds a fraction of a vote that a ballot counts for a given candidate
 // after finding the candidate in the ballot's who-for array
 // if the candidate is not there, a new who-for entry is created,
 // but the ballot is not yet in the candidate's pile.
 // next contains garbage prior to loading all ballots and building piles
 // To conserve memory, if an entry is set to zero weight and is at the end of the array,
 // it is deleted.
 {
  int index=Ballot::getwho(whichcand);
  if (index==Ballot::wt.size())
  {
   tempwho.who=whichcand;
   tempwho.weight=thewt;
   tempwho.next=BalCt;
   Ballot::wt.push_back(tempwho);
  }
  else
  {
   Ballot::wt[index].weight+=thewt;
   if (index==Ballot::wt.size()-1 && ElimFlag[Ballot::wt[index].who]==-1)
    Ballot::wt.pop_back();
  }
 }

 void sortwt()
 // Sorts the who-for array according to the candidate order given by
 // the ballot's list of rankings (used for generating the pile file)
 {
  int i0,i1;
  for (i0=1; i0<Ballot::wt.size(); i0++)
  {
   tempwho=Ballot::wt[i0];
   i1=i0;
   while (Ballot::GetPos(Ballot::wt[i1-1].who) >
          tempwho.who)
   {
    Ballot::wt[i1] = Ballot::wt[i1-1];
    i1--;
    if (i1<=0) break;
   }
   Ballot::wt[i1] = tempwho;
  }
 }
 
 char HighRank()
 // returns who the ballot currently counts for (according to Ballot::pos).
 // returns the number of candidates if ballot is exhausted.
 {
  if (Ballot::pos>=Ballot::ranklist.size() || Ballot::pos==Cands)
  { return Cands; }
  else
  { return Ballot::ranklist[Ballot::pos]; }
 }

 char tally(double Surp, bool xfer=false, bool dodge=false)
 // determine who a ballot counts for, given current conditions.
 // If the ballot is to be transferred, Surp is the fraction to transfer
 // xfer is false when loading the ballot for the first time, true otherwise
 // when loading for the first time, weight must be set separately
 // dodge is true if transfers are being avoided if a ballot exhausts
 {
  char OldPos=Ballot::pos;
  char OldHR=Ballot::HighRank();
  double BalTalWt; // amount to transfer
  if (xfer) BalTalWt=Ballot::getweight(OldHR)*Surp;
   else BalTalWt=1.0; 
  bool LastPos=0; // stop moving through list if 1
  char Recycleto=Cands; // where ballot goes if end of list is reached
  // Cands means the ballot exhausts

  Ballot::pos=0; // start at beginning of list  
  char hr=Ballot::HighRank();

  while(!LastPos)
  {
   if (Ballot::pos==Cands) break;
   if (Ballot::pos==Ballot::ranklist.size()-1) LastPos=1; 
   // don't go beyond end of list

   if (ElimFlag[hr]==0) break;
   // if found a continuing candidate, stay there
   else
   {
    if (Ballot::pos==OldPos && ElimFlag[hr]==2) break;
    // if previously counted for an elected candidate not yet transferred, stay there
    else
    {
     if (DoRecycle && Recycleto==Cands && ElimFlag[hr]>0)
      Recycleto=Ballot::pos;
     // if first already-elected candidate is found, remember who it is
     // (unless not using the recycle feature)

     // if we are at the end of the list, we are about to exhaust, so
     // leave ballot where it was if transferring from a winner
     // give it to highest-ranked winner otherwise (if recycling) or let it exhaust
     if (LastPos)
     {
      if (dodge && ElimFlag[OldHR]>0) Ballot::pos=OldPos;
      else Ballot::pos=Recycleto;
      hr=Ballot::HighRank();
      break;
     }
    } 
   } 
   Ballot::pos++;
   hr=Ballot::HighRank();
  }

  NewTally[hr]+=BalTalWt;          // add vote to tally
  Ballot::addweight(hr,BalTalWt);  // account for vote on ballot

  // subtract old vote and adjust weights if transferring
  if (xfer)
  {
   NewTally[OldHR]-=BalTalWt;
   Ballot::addweight(OldHR,-BalTalWt);
  }
  return hr; 
 }

 double ExhaustCheck()
 // determine whether ballot will exhaust upon transfer, neglecting recycling
 // - much like tally()

 {
  char OldPos=Ballot::pos;
  double BalTalWt=Ballot::getweight(Ballot::HighRank());
  bool LastPos=0; // escape loop with breaks, not LastPos
  Ballot::pos=0;
  char hr=Ballot::HighRank();

  while(!LastPos)
  {
   if (Ballot::pos==Cands) { break; }

   if (ElimFlag[hr]==0) break;
   // if found a continuing candidate, stay there
   else
   {
    if (Ballot::pos==OldPos && ElimFlag[hr]==2) break;
    // if previously counted for an elected candidate not yet transferred, stay there
    else
    {
     // ballot exhausts if it's at the end of the list and hasn't counted for someone yet
     if (Ballot::pos==Ballot::ranklist.size()-1)
     { Ballot::pos=Cands; break; }
    } 
   } 
   Ballot::pos++;
   hr=Ballot::HighRank();
  }
  if (Ballot::pos==Cands) { BalTalWt=0.0; }
  Ballot::pos=OldPos;
  return BalTalWt;
 }
} tempbal;
vector<Ballot> Ballots;
Ballot NuBal;

class Pile
// Provides a shell for a 1-way linked list of ballots counting for a given candidate
// and keeps track of when a subset of ballots was added to the pile
{
 public:
 long first,last; // index in the ballot array of the first and last ballot in the pile
 vector<long> recent, recentrnd; 
 // index of ballot just before a new subset is added
 // and the round after which that subset is added (starting from 1).

 void reset()
 {
  Pile::last=BalCt;
  Pile::first=BalCt;
  Pile::recent.clear();
  Pile::recentrnd.clear();
  Pile::recent.push_back(Pile::last);
  Pile::recentrnd.push_back(0); 
 }

 void build(long BalNum)
 // add ballot to list just after loading
 {
  if (Pile::last==BalCt)
  { Pile::first=BalNum; }
  else
  { Ballots[Pile::last].wt[0].next=BalNum; } 
  Pile::last=BalNum;
  Ballots[BalNum].wt[0].next=BalCt; 
 }

 void xfer(long BalNum)
 // add ballot to list, checking to make sure it's not already there
 {
  char hr=Ballots[BalNum].HighRank();
  if (Ballots[BalNum].getnext(hr)==BalCt && Pile::last!=BalNum)
  {
   if (Pile::last==BalCt) Pile::first=BalNum;
   else Ballots[Pile::last].setnext(hr,BalNum); 
   Pile::last=BalNum;
  }
 }
} tempile;
vector<Pile> Piles;

//------------------------------------------------------------Functions
void XferBallots(int who)
// goes through a pile of ballots and transfers them to their next choices
{
 bool DoDodge=Dodgex && (ElimFlag[who]==1);
 int recentbal; // start of most recent subset of a winner's pile
 long XferPtr, RecentCtr, NextXP; // pointers used to step through lists
 NewTally=VoteMatrix[RndNum-1]; // redundant?

 // Record the last ballot in the pile of each candidate not awaiting transfer
 // and the current round so the start of the subset of the pile added here
 // can be determined later
 for (int i=0; i<Cands; i++)
 {
  if (i!=who && ElimFlag[i]!=2 && Piles[i].recent[Piles[i].recent.size()-1]!=Piles[i].last)
  { Piles[i].recent.push_back(Piles[i].last); Piles[i].recentrnd.push_back(RndNum); } 
 } 
 
 // if a surplus transfer (as opposed to elimination of a loser)
 if (ElimFlag[who]==1)
 {
  // If a recent subset exists, transfer those ballots.  Otherwise transfer them all.
  recentbal=Piles[who].recent[Piles[who].recent.size()-1];
  if (recentbal!=Piles[who].last
     && recentbal!=BalCt)
   XferPtr=Ballots[recentbal].getnext(who);
  else XferPtr=Piles[who].first;
 
  // Determine how many recent subsets are needed if we are dodging exhausted ballots
  if (DoDodge)
  {
   // work backwards through subsets until we have enough votes to transfer entire surplus
   RecentCtr=Piles[who].recent.size(); // which subset we are on
   double Noexsum=0.0; // number of votes accumulated that won't exhaust
   long StopPtr=BalCt; // marks end of subset
  
   while ((Noexsum<SurplusNum[RndNum-1]) && (RecentCtr>0))
   {
    RecentCtr--;
  
    // if no more subsets, transfer whole pile.
    // Otherwise, start at beginning of current subset.  
    if (RecentCtr==0 || Piles[who].recent[RecentCtr]==BalCt)
     XferPtr=Piles[who].first;
    else
     XferPtr=Ballots[Piles[who].recent[RecentCtr]].getnext(who);
 
   // step through subset, adding up number of non-exhausting votes    
    while(XferPtr!=StopPtr)
    {
     Ballots[XferPtr].pos=Ballots[XferPtr].GetPos(who);
     Noexsum+=Ballots[XferPtr].ExhaustCheck();
     XferPtr=Ballots[XferPtr].getnext(who);
    }
   
    // mark end of pile for next iteration, if needed
    if (Piles[who].recent[RecentCtr]<BalCt)
    StopPtr=Ballots[Piles[who].recent[RecentCtr]].getnext(who);
    else StopPtr=BalCt;
   } // backwards loop through subsets
  
   // If we didn't find enough, prepare to transfer whole pile
   // If we did, we'll transfer just the subsets examined, remember which subsets were used
   // (so votes can be tallied without a full recount later).
   // determine the surplus fraction based on the number of votes that will transfer
   if (Noexsum<SurplusNum[RndNum-1] || RecentCtr==0 || Piles[who].recent[RecentCtr]==BalCt)
   { XferPtr=Piles[who].first; XferFromRnd[RndNum]=1; }
   else
   {
    XferPtr=Ballots[Piles[who].recent[RecentCtr]].getnext(who);
    XferFromRnd[RndNum]=Piles[who].recentrnd[RecentCtr];
   }
   SurplusDenom[RndNum-1]=(Noexsum>SurplusNum[RndNum-1]?Noexsum:SurplusNum[RndNum-1]);
  }
 }
 else
 // doing elimination - prepare to transfer whole pile
 { XferPtr=Piles[who].first; XferFromRnd[RndNum]=0; }
 
 Surplus[RndNum-1]=SurplusNum[RndNum-1]/SurplusDenom[RndNum-1]; 
 
 // step through the pile (or subset of it) and transfer a fraction of
 // ballots to next choices (add to the tally and move to new piles)
 char hr;
 while(XferPtr!=BalCt)
 {
  NextXP=Ballots[XferPtr].getnext(who);
  Ballots[XferPtr].pos=Ballots[XferPtr].GetPos(who);
  hr=Ballots[XferPtr].tally(Surplus[RndNum-1],true,DoDodge);
  Piles[hr].xfer(XferPtr);
  XferPtr=NextXP; 
 }

 // delete pile info for eliminated candidate
 if (ElimFlag[who]==-1) Piles[who].reset();
} 

void XferMyBallot(int who)
// Transfer the ballot given on the command line
// Should be called after XferBallots, not before, to ensure XferFromRnd is up to date
{
 bool DoDodge=Dodgex && (ElimFlag[who]==1);

 // if vote is in the pile of a winner but is not recent, don't xfer again
 // but reconsider if the winner is transferred from during equalization
 if (who==(int)NuBal.HighRank()) DoMyXfer=1;
 else if (ElimFlag[NuBal.HighRank()]==1) DoMyXfer=0;

 if (ElimFlag[who]>0 && XferFromRnd[RndNum]>1)
 {
  for (int i0=0; i0<XferFromRnd[RndNum]; i0++)
   if (MyOrder[i0]==who) DoMyXfer=0;
 }

 // tally the vote (it may not transfer) but don't add to tally.
 if (DoMyXfer)
 {
  OldTally=NewTally;
  if (NuBal.getweight(who)>PRECISION) NuBal.pos=NuBal.GetPos(who);
  MyOrder[RndNum]=(int)NuBal.tally(Surplus[RndNum-1],true,DoDodge);
  NewTally=OldTally;
 }
 else MyOrder[RndNum]=MyOrder[RndNum-1]; 
}

void DoDepth()
// Go through ballots and compute some statistics:
// Depth: sum of weights from ballots that counted for a winner in each ballot position
// FracDepth: Number of ballots that counted for a given number of winners
// RankCt: Number of ballots that ranked a given number of candidates
// MyDepth: weights from command line ballot that counted for a winner in each ballot position
// MyTally: who the command line ballot counted for
{
 int i,h,k;

 // Compute depth and tally for command line ballot first
 if (ValidBallot)
 {
  for (i=0; i<NuBal.wt.size(); i++)
  {
   k=NuBal.GetPos(NuBal.wt[i].who);
   if (k!=Cands && ElimFlag[NuBal.wt[i].who]>0)
    MyDepth[k]+=NuBal.wt[i].weight;
   else MyDepth[Cands]+=NuBal.wt[i].weight;
   if (NuBal.wt[i].weight>PRECISION) MyTally[NuBal.wt[i].who]+=NuBal.wt[i].weight;
  }
 }

 long fd; // number of nonzero entries in whofor array (for fracdepth)
 for (i=0; i<=Cands; i++) Depth[i]=0;
 for (h=0; h<BalCt; h++)
 {
  fd=0;
  RankCt[(Ballots[h].ranklist.size()-1)>Cands?Cands:(Ballots[h].ranklist.size()-1)]++;
  for (i=0; i<Ballots[h].wt.size(); i++)
  {
   if (Ballots[h].wt[i].weight>PRECISION) fd++;
   k=Ballots[h].GetPos(Ballots[h].wt[i].who);
   if (k!=Cands && ElimFlag[Ballots[h].wt[i].who]>0)
    Depth[k]+=Ballots[h].wt[i].weight;
   else Depth[Cands]+=Ballots[h].wt[i].weight;
  }
  if (fd>0) FracDepth[fd-1]++;
 }
} 

int ResolveTies(int Tier, double TieVotes, int W)
// Tries to break ties using successively previous rounds, then by lot
// Tier is one of the candidates who may have tied
// TieVotes is the number of votes received by Tier
// W=1 for winner and -1 for loser
// returns who broke the tie
// records, in Tie array, result for each round:
// 0 if no ties, -1 if a tie was broken by lot
// otherwise the round number, starting from 1, in which a tie was resolved
{
 int TieBreaker=0; // previous-round equivalent of tier
 double TieBreakVotes; // previous-round equivalent of TieVotes

 int NumOfTies=0;
 int i;
 vector<int> LTies; // array of candidates who tie

 // Find any continuing candidates tied with Tier in the current tally
 // not counting ties among non-transferred winners who won in different rounds
 for (i=0; i<Cands; i++)
 {
  if ((Tier!=i) &&(round(NewTally[i]-TieVotes)==0.0) && 
      (ElimFlag[i]==0 || ElimFlag[i]==2))
  {
   if (!(W==1 && ElimFlag[Tier]==2 && ElimFlag[i]==2 &&
         WonInRound[Tier]!=WonInRound[i]))
   {
    NumOfTies++;
    LTies.push_back(i);
   }
  } 
 } 
 int OrigNumOfTies=NumOfTies;
 LTies.push_back(Tier);

 // Step backwards through rounds and see if ties can be broken
 // continue until tie is broken or no more previous rounds
 int TieRnd=RndNum;
 while((TieRnd>0) && (NumOfTies>0))
 {
  TieRnd--;
  // Determine plurality winner/loser among tied cnds for this round
  TieBreakVotes=BalCt*(double)((1-W)/2); // zero or BalCt, as appropriate
  for (i=0; i<=NumOfTies; i++)
  {
   if (((double)W*VoteMatrix[TieRnd][LTies[i]]>(double)W*TieBreakVotes))
   {
    TieBreakVotes=VoteMatrix[TieRnd][LTies[i]];
    TieBreaker=i;
   } 
  }

  // identify remaining tiers (no regard for elimflag here)
  // if still tied, put at end of the list as measured by TieTemp
  // (untied candidates are overwritten and entries beyond TieTemp will be ignored)
  int TieTemp=0;
  int OldTieBreaker=LTies[TieBreaker];
  for (i=0; i<=NumOfTies; i++)
  {
   if (i!=TieBreaker && round(VoteMatrix[TieRnd][LTies[i]]-TieBreakVotes)==0.0)
   {
    TieTemp++;
    LTies[TieTemp-1]=LTies[i];
   } 
  }

  LTies[TieTemp]=OldTieBreaker;
  NumOfTies=TieTemp;
 } // stepping backwards through rounds 

 //Break ties by lot if prev round method fails
 if (NumOfTies>0)
 {
  int Lot=(int)floor(((double)NumOfTies+1.0)*((double)(rand()%RAND_MAX)/(double)RAND_MAX));
  Ties[RndNum]=-1;
  return LTies[Lot];
 }
 else
 // return who broke tie and record round in which broken, starting from 1
 {
  if (OrigNumOfTies>0) Ties[RndNum]=TieRnd+1;
  else Ties[RndNum]=0;
  return LTies[TieBreaker];
 } 
}

void piledump()
// for debugging, using small test polls - dumps the contents of each pile to the screen
{
 int i;
 long j;

 cout << RndNum << " ";
 if (RndNum>0) cout << ElimOrder[RndNum-1];
 cout << endl;
 for (i=0; i<=Cands; i++)
 {
  cout << i << ":";
  j=Piles[i].first;
  while (j!=BalCt)
  {
   cout << j << " (" << (int)Ballots[j].HighRank() << ") ";
   j=Ballots[j].getnext(i);
  }
  cout << endl;
 }
}

//------------------------------------------------------------------MAIN
int main(int argc, char *argv[])
{
 srand(time(NULL)); // seed random number generator with timestamp
 char inbuf[1024]; // used when loading ballots
 char exclstr[1024]; // used to process command line strings
 char* exclchar;
 bool DoExclude=0;
 bool Verbose=1;
 int h,i,j,hr;
 long k;

 //-------------------- parse command line
 int argtype=0;
 char label[80];
 char fname[80];
 strcpy(label,DEFAULT_PATH);
 strcat(label,DEFAULT_FILE);
 if (Verbose) cout << THISFILE << endl;
 for (int i=1; i<argc; i++)
 {
  switch (argtype)
  {
   case 1: // -s <num>: set number of seats to elect (between 1 and 255)
    Seats=atoi(argv[i]);
    Seats=Seats<1?1:Seats;
    Seats=Seats>255?255:Seats;
    argtype=0; break;
   case 2: // -f <label: set file prefix (1-20 chars)
    if (strlen(argv[i])>0 && strlen(argv[i])<20)
    { strcpy(label,DEFAULT_PATH); strcat(label,argv[i]); }
    argtype=0; break;
   case 3: // -c <num>: set number of candidates (between 1 and 255)
    Cands=atoi(argv[i]);
    Cands=Cands<1?1:Cands;
    Cands=Cands>255?255:Cands;
    argtype=0; break;
   case 4: // -b <ballot>: enter ballot from the command line to see who it counts for
    // same format as ballot input file
    strcpy(inbuf,argv[i]);
    if (NuBal.load(inbuf)>0)
     ValidBallot=1; else ValidBallot=0;
    argtype=0; break;
   case 5: // -x <list>: candidates to exclude, comma-delimited
    strcpy(exclstr,argv[i]);
    DoExclude=1;
   argtype=0; break;
   default:
   if (strcmp(argv[i],"-s")==0) argtype=1;
   if (strcmp(argv[i],"-f")==0) argtype=2;
   if (strcmp(argv[i],"-c")==0) argtype=3;
   if (strcmp(argv[i],"-b")==0) argtype=4;
   if (strcmp(argv[i],"-x")==0) argtype=5;
   if (strcmp(argv[i],"-q")==0) Verbose=0;
   if (strcmp(argv[i],"-p")==0) DoPile=1;
   if (strcmp(argv[i],"-r")==0) DoRecycle=0;
   if (strcmp(argv[i],"-e")==0) DoEqualize=0;
   if (strcmp(argv[i],"-n")==0) NoEx=0;
   if (strcmp(argv[i],"-d")==0) Dodgex=0;
   if (strcmp(argv[i],"-h")==0)
   {
    cout << "DemoChoice tally software - http://www.demochoice.org" << endl;
    cout << "inputs from command line:" << endl;
    cout << " -f <name> filename prefix" << endl;
    cout << "  if <name>=DC, this corresponds to DC_ballots.txt DC_tally.txt DC_piles.txt" << endl;
    cout << "  files should be in subdirectory called data" << endl;
    cout << " -s <num> seats to be elected" << endl;
    cout << " -c <num> number of candidates, up to 255" << endl;
    cout << " -x 0,1,2 candidates to exclude (0,1, and 2 in this case)" << endl;
    cout << " -p create pile file (shows who each vote counted for)" << endl;
    cout << " -r turn off exhausted ballot and residual candidate recycling" << endl;
    cout << " -d turn off exhausted ballot dodging during surplus transfers" << endl;
    cout << " -n turn off exhausted ballot correction of threshold" << endl;
    cout << " -q turn off display of most results to stdout (screen)" << endl;
    cout << " -b 0,1,2,3,4 (rankings in order) see who a ballot would count for" << endl;
    cout << "input file: filename_ballots.txt" << endl;
    cout << " each line contains one ballot, listing choices in order (1st, 2nd, ...) separated by commas" << endl;
    cout << " candidates are numbered 0,1,2,... in the order listed in a separate file not directly used" << endl;
    cout << " by this program" << endl;
    cout << "output pile file: filename_piles.txt" << endl;
    cout << "each line corresponds to the same line in the ballot file" << endl;
    cout << " in order of the ballot's preference, lists each winner that the ballot counted for" << endl;
    cout << " followed by the fraction of the ballot that the candidate received (comma-delimited)." << endl;
    cout << "output tally file: filename_tally.txt" << endl;
    cout << " dumps the final values of most of the variables used in the count, " << endl;
    cout << " as documented in DemoChoice-Readme.txt." << endl;
   }
  }
 }
 // if more candidates than seats, default to 1 seat
 Seats=Seats<(Cands+1)?Seats:1;

 //----------------------- initialize arrays
 // Max # rounds is Cands+1 regular rounds and residual eliminations + Seats equalization rounds
 // Cands arrays are set to Cands+1 for consistency even though some (all?) logically only need Cands 
 ElimFlag.assign(Cands+1,0);
 RankCt.assign(Cands+1,0);
 WonInRound.assign(Cands+1,0);
 XferFromRnd.assign(Cands+Seats+1,0);
 Ties.assign(Cands+Seats+1,0);
 Surplus.assign(Cands+Seats+1,1.0);
 NewTally.assign(Cands+1,0);
 Depth.assign(Cands+1,0);
 FracDepth.assign(Cands+1,0);
 MyDepth.assign(Cands+1,0);
 MyTally.assign(Cands+1,0);
 MyOrder.assign(Cands+Seats+1,Cands);
 SurplusNum.assign(Cands+Seats+1,1.0);
 SurplusDenom.assign(Cands+Seats+1,1.0);
 ElimOrder.assign(Cands+Seats+1,0);

 // eliminate excluded candidates
 // similar to Ballot::load()
 if (DoExclude)
 {
  exclchar = strtok(exclstr,",");
  while (exclchar != NULL)
  {
   h=abs(atoi(exclchar));
   h=h>Cands?Cands:h;
   ElimFlag[h]=-1;
   if (Verbose) cout << h << " excluded" << endl;
   exclchar = strtok (NULL,",");
  }
 }

//-------------------------------- load ballots
 
 if (ValidBallot)
 {
  OldTally=NewTally;
  NuBal.tally(1.0);
  NewTally=OldTally;
  MyOrder[0]=NuBal.HighRank();
 }
 strcpy(fname,label);
 ifstream infile(strcat(fname,"_ballots.txt"),ios::in);
 if (infile) { if (Verbose) cout << "opened " << fname << endl; }
 else { cout << "failed to open " << fname << endl; }
 long start_time=clock();
 while (infile)
 {
  infile.getline(inbuf,1024);
  if (infile)
  {
   if (tempbal.load(inbuf)>0)
   {
    tempbal.tally(1.0);
    Ballots.push_back(tempbal);
   }
  }
 }
 infile.close();
 BalCt=Ballots.size();


// build piles of ballots for first-choice candidate
 tempile.reset();
 for (i=0; i<=Cands; i++) Piles.push_back(tempile);
 for (k=0; k<BalCt; k++) Piles[Ballots[k].HighRank()].build(k);

 int Remaining=Cands; // number of remaining candidates
 int Elected=0;       // number of elected candidates
 
 // continuing candidate with most/least votes in current round
 int Loser, Winner;   
 double LoserVotes,WinnerVotes;

 // Droop threshold - OldThresh is remembered in case NoEx=1 (correcting for exhausted) 
 double Thresh=((double)BalCt)/((double)(Seats+1)); // Droop threshold
 double OldThresh=Thresh;

 RndNum=0;

 // skip main loop if no votes in ballot file
 if (BalCt==0) Elected=Seats;

 //------------------------------------------ main loop
 while (Elected<Seats)
 {
  ElimOrder[RndNum]=0;

  // transfer votes from the candidate identified in the previous round
  if (RndNum>0)
  {
   XferBallots(abs(ElimOrder[RndNum-1])-1);
   if (ValidBallot) XferMyBallot(abs(ElimOrder[RndNum-1])-1);
  }
  VoteMatrix.push_back(NewTally);
  XferMatrix.push_back(NewTally);
  if (RndNum>0)
  {
   for (i=0; i<=Cands; i++)
   XferMatrix[XferMatrix.size()-1][i]-=VoteMatrix[VoteMatrix.size()-2][i];
  }

  // update number of remaining candidates
  Remaining=0;
  for (i=0; i<Cands; i++) if (ElimFlag[i]>=0) Remaining++;

  // if there are no more candidates left to eliminate, abort all surplus transfers
  // and declare remaining candidates elected
  if (Remaining<=Seats || Elected>=Seats)
  {
   Elected=Seats;
   for (i=0; i<Cands; i++)
   { if (ElimFlag[i]>=0) ElimFlag[i]=1; }
  }

  // otherwise determine a candidate to transfer from
  else
  {
   // update threshold to correct for exhausted ballots (does nothing if NoEx=0)
   Thresh=OldThresh*(1.0-((double)NoEx)*NewTally[Cands]/((double)BalCt));

   // Determine plurality winner and loser for this tally
   Loser=0;
   Winner=0;
   LoserVotes=(double)BalCt;
   WinnerVotes=0.0;
 
   for (i=0; i<Cands; i++)
   {
    if ((NewTally[i]<LoserVotes) && (ElimFlag[i]==0))
    {
     LoserVotes=NewTally[i];
     Loser=i;
    } 

    // plurality winner must properly handle those who have already won but haven't been transferred yet
    // if i have already won and the plurality winner so far hasn't, i beat PW.  
    // if i have already won and so has P.W. but P.W. won later, i beat PW.
    // if i have more votes, and P.W. hasn't won yet, i beat PW.
    // if i have more votes, and PW has won, but not in an earlier round, i beat PW.
    if ((ElimFlag[i]==2 &&
         (ElimFlag[Winner]==0 ||
          (ElimFlag[Winner]==2 && WonInRound[Winner]>WonInRound[i])
        )) ||
        ((NewTally[i]>WinnerVotes) &&
         (ElimFlag[i]==0 || ElimFlag[i]==2) &&
        !((ElimFlag[Winner]==2 && WonInRound[Winner]<RndNum) &&
          (ElimFlag[i]==0 ||
           (ElimFlag[i]==2 && WonInRound[Winner]<WonInRound[i])
       ))))
    {
     WinnerVotes=NewTally[i];
     Winner=i;
    } 

    // If a continuing candidate is now above the threshold,
    // remember the round in which this happened, declare elected,
    // and mark for future surplus transfer
    if (NewTally[i]>Thresh && (ElimFlag[i]==0))
    {
     ElimFlag[i]=2;
     Elected++;
     WonInRound[i]=RndNum;
    } 
   } // plurality for loop 

   SurplusNum[RndNum]=1.0;
   SurplusDenom[RndNum]=1.0;
   Surplus[RndNum]=1.0;

   // if there is a surplus to transfer, resolve ties, mark for next transfer,
   // and determine the surplus (which will be revised if dodging exhausted ballots)
   if (WinnerVotes>Thresh)
   {
    Winner=ResolveTies(Winner,WinnerVotes,1);
    if (XferMatrix[WonInRound[Winner]][Winner]>0)
    {
     SurplusNum[RndNum]=WinnerVotes-Thresh;
     SurplusDenom[RndNum]=XferMatrix[WonInRound[Winner]][Winner];
     Surplus[RndNum]=SurplusNum[RndNum]/SurplusDenom[RndNum];
    }
    ElimFlag[Winner]=1;
    ElimOrder[RndNum]=(Winner+1); 
   }
   else
   //Nobody won in this round, so mark the loser for elimination
   {
    Loser=ResolveTies(Loser,LoserVotes,-1);
    ElimFlag[Loser]=-1;
    ElimOrder[RndNum]=(-(Loser+1));
   } // whether there was a winner
  } // else more candidates than seats

  RndNum++;

 } // while elected<seats

 // Non-transferred winners no longer require special status, so make them ordinary winners
 for (i=0; i<Cands; i++) if (ElimFlag[i]==2) ElimFlag[i]=1;

 //--------------------------------------
 // The winners are now determined.  The following steps help clarify the constituencies of these winners.
 // Eliminate and transfer any lingering non-elected candidates who are tying up votes
 // Order has no effect here, so no effort is made to do this in order of number of votes
 // (it would be very rare to have more than one residual candidate anyway)
 if (BalCt>0 && RndNum>0 && DoRecycle)
 {
  for (i=0; i<Cands; i++)
  {
   if (ElimFlag[i]==0)
   {
    ElimFlag[i]=-1; // Outside the next if to quietly eliminate zero-vote candidates
    if (VoteMatrix[RndNum-1][i]>0)
    {
     SurplusNum[RndNum-1]=1.0;
     SurplusDenom[RndNum-1]=1.0;
     Surplus[RndNum-1]=1.0;
     ElimOrder[RndNum-1]=-(i+1);
     XferBallots(i);
     if (ValidBallot) { XferMyBallot(i); } 
     VoteMatrix.push_back(NewTally);
     XferMatrix.push_back(NewTally);
     for (j=0; j<=Cands; j++)
      XferMatrix[XferMatrix.size()-1][j]-=VoteMatrix[VoteMatrix.size()-2][j];
     RndNum++;
    } // votematrix if
   } // elimflag if
  } // for loop
 } // recycle residual candidates

 // Arrange so that winners have equal-sized constituencies.
 // (This procedure doesn't make sense if we are not also recycling.)
 // Selects the winner with the most votes and reduces to the Hare threshold,
 // transferring votes to winners below that threshold
 // This repeats Seats times or until each winner has reached the threshold.
 if (BalCt>0 && RndNum>0 && DoEqualize && DoRecycle)
 {
  // we will need to temporarily change elimflags, and restore them later
  vector<int> OldElimFlags=ElimFlag;

  // Determine Hare threshold, corrected for exhausted ballots
  double MaxThresh=(((double)BalCt-NewTally[Cands])/(double)Seats);

  // initially all winners are eligible to receive votes
  for (j=0; j<Cands; j++)
   if (ElimFlag[j]>0) ElimFlag[j]=0;

  bool TooMany=0; // a candidate has too many votes
  int Counter=Seats; // number of times tried 
  while (Counter>0)
  {
   Counter--;
   TooMany=0;
   // identify winners with too many votes and who have not been transferred from yet
   // set TooMany flag if one is found 
   for (j=0; j<Cands; j++)
    if (ElimFlag[j]!=1 && NewTally[j]>MaxThresh+PRECISION)
     { ElimFlag[j]=2; TooMany=1; }

   if (TooMany)
   {
    // Determine plurality winner
    Winner=0;
    WinnerVotes=0;
    for (j=0; j<Cands; j++)
    {
     if (ElimFlag[j]==2 && NewTally[j]>WinnerVotes)
     {
      WinnerVotes=NewTally[j];
      Winner=j;
     }
    }
    Winner=ResolveTies(Winner,WinnerVotes,1);
    ElimFlag[Winner]=1;

    // Do transfer for eligible winner, if any
    if (NewTally[Winner]>MaxThresh+PRECISION && VoteMatrix[RndNum-1][Winner]>0)
    {
     SurplusNum[RndNum-1]=NewTally[Winner]-MaxThresh;
     SurplusDenom[RndNum-1]=NewTally[Winner];
     Surplus[RndNum-1]=SurplusNum[RndNum-1]/SurplusDenom[RndNum-1];
     ElimOrder[RndNum-1]=(Winner+1);
     XferBallots(Winner);
     if (ValidBallot) { XferMyBallot(Winner); }
     VoteMatrix.push_back(NewTally);
     XferMatrix.push_back(NewTally);
     for (j=0; j<=Cands; j++)
      XferMatrix[XferMatrix.size()-1][j]-=VoteMatrix[VoteMatrix.size()-2][j];
     RndNum++;
    } // if eligible winner found
   } // if toomany
  } // while counter>0

  // Restore old ElimFlag values
  ElimFlag=OldElimFlags;
 } // if DoEqualize

 int Rnds=RndNum;
 bool comma; // used in formatting output to avoid fencepost errors

 // compute statistics on final status of ballots
 if (BalCt>0) DoDepth();

 //------------------------------------------ Dump summary of results to screen
 if (BalCt>0)
 {
  if (Verbose)
  {
   cout << clock()-start_time << " ms to tally" << endl;
   cout << "Electing " << Seats << " of " << Cands << " candidates with ";
   cout << BalCt << " ballots" << endl;
   for (j=0; j<RndNum; j++)
   {
    cout << j << ":";
    for (i=0; i<=Cands; i++)
     if (VoteMatrix[j][i]>PRECISION) 
      cout << round(VoteMatrix[j][i]) << "|";
     else cout << "0|";
    cout << ElimOrder[j] << endl;
   }
   cout << "FracDepth |";
   for (i=0; i<=Cands; i++) cout << round(FracDepth[i]) << "|";
   cout << endl;
   cout << "Depth |"; 
   for (i=0; i<=Cands; i++) cout << round(Depth[i]) << "|";
   cout << endl;
  }
 
  // Dump results of command-line ballot tally to screen/stdout
  if (ValidBallot)
  {
   cout << "MyBallot:" << endl;

   if (Verbose)
   {
    cout << "(MyDepth, MyOrder, MyTally)" << endl;

    // command-line ballot's depth (how much each ranking was counted)
    comma=0;
    for (i=0; i<=Cands; i++)
     {
      if (comma) cout << ","; else comma=1;
      cout << MyDepth[i];
     }
    cout << endl;
   }

   // lowest ranking used in a given round
   comma=0;
   for (i=0; i<Rnds; i++)
    {
     if (comma) cout << ","; else comma=1;
     cout << MyOrder[i];
    }
   cout << endl;

   // who it counted for
   comma=0;
   for (i=0; i<=Cands; i++)
    {
     if (comma) cout << ","; else comma=1;
     cout << MyTally[i];
    }
   cout << endl;
  } // if valid ballot
 } // BalCt > 0
 else
 {
  cout << "No ballots in file" << endl;
  if (ValidBallot)
  {
   for (i=0; i<Cands; i++)
   { MyTally[i]=0.0; cout << "0,"; }
   MyTally[Cands]=1.0;
   cout << "1" << endl;
  }
 }
 //--------------------------------- Pile File
 if (BalCt>0)
 {
  if (DoPile)
  {
   cout << "doing pile file" << endl;
   strcpy(fname,label);
   ofstream pilefile(strcat(fname,"_piles.txt"));
   if (pilefile) /* cout << "opened " << fname << endl */;
   else cout << "failed to open " << fname << endl;

   for (h=0; h<BalCt; h++)
   {
    Ballots[h].sortwt();
    comma=0;
    for (i=0; i<Ballots[h].wt.size(); i++)
    {
     if (pilefile.good() && Ballots[h].wt[i].weight>0.00001)
     {
      if (comma) pilefile << ","; else comma=1;
      pilefile << (int)Ballots[h].wt[i].who << ","
       << Ballots[h].wt[i].weight;
     }
    }
    pilefile << endl;
   }
   pilefile.close();
  }
 } // BalCt>0

 //---------------------------------Rewrite Tally File
 if (BalCt>0)
 {
  strcpy(fname,label);
  ofstream outfile(strcat(fname,"_tally.txt"));
  if (outfile) { if (Verbose) cout << "opened " << fname << endl; }
  else cout << "failed to open " << fname << endl;

  outfile << "Ballots   |" << BalCt << endl;

  outfile << "Threshold |" << OldThresh << "|Droop" << endl;

  for (j=0; j<Rnds; j++)
  {
   outfile << "Transfer  ";
   for (i=0; i<=Cands; i++)
    outfile << "|" << round(XferMatrix[j][i]);
   outfile << "|" << endl;

   outfile << "Tally     ";
   for (i=0; i<=Cands; i++)
    outfile << "|" << round(VoteMatrix[j][i]);
   outfile << "|" << ElimOrder[j] << endl;
  } 
 
  outfile << "Status    ";
  for (i=0; i<Cands; i++)
   outfile << "|" << ElimFlag[i]; 
  outfile << "|0|" << endl;

  outfile << "XferFrom  ";
  for (i=0; i<Rnds; i++)
   outfile << "|" << XferFromRnd[i];
  outfile <<  "|0|" << endl;

  outfile << "Surplus   ";
  for (i=0; i<Rnds; i++)
   outfile << "|" << Surplus[i]; 
  outfile << "|0|" << endl;

  outfile << "FracDepth ";
  for (i=0; i<=Cands; i++)
   outfile << "|" << FracDepth[i]; 
  outfile << "|" << endl;

  outfile << "Depth     ";
  for (i=0; i<Cands; i++)
   outfile << "|" << round(Depth[i]);
  outfile << "|" << round(VoteMatrix[Rnds-1][Cands]) << "|"<< endl;

  outfile << "RankCt    ";
  for (i=0; i<Cands; i++)
   outfile << "|" << RankCt[i];
  outfile << "|0|" << endl;

  outfile << "Ties      ";
  for (i=0; i<Rnds; i++)
  { outfile << "|" << Ties[i]; } 
  outfile << "|0|" << endl;

  outfile.close();
 } // balct > 0

 return 0;
}
