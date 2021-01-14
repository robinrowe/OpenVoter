package elections

import scala.math.BigDecimal.RoundingMode

class SingleTransferableVoteElectionResult(val rounds: Seq[STVRoundResult], val quota: BigDecimal) extends ElectionResult {
  lazy val winners: Seq[Candidate] = rounds.flatMap(r => r.winners)
}

case class STVRoundResult(
                           firstPlaceVotes: Map[Candidate, BigDecimal],
                           exhaustedBallotWeight: BigDecimal,
                           winners: Set[Candidate],
                           loser: Option[Candidate],
                           diversityProtected: Set[Candidate],
                           diversityExcluded: Set[Candidate],
                           tieBreakResult: Option[TieBreakResult]
                         )

object SingleTransferableVoteElection {
  // Droop Quota is more commonly used, and can be thought of as the smallest number of ballots a candidate can be on
  // where they have a mandate.
  // Note that the floor function is implicit in integer division
  val DroopQuota: (Int, Int) => BigDecimal = (numBallots, numCandidates) => (numBallots / (numCandidates + 1)) + 1

  // The Hare Quota can be thought of as the largest number of ballots before you have to select that candidate.
  // Using the Hare Quota tends toward under-representing larger groups.
  val HareQuota: (Int, Int) => BigDecimal = (numBallots, numCandidates) => BigDecimal(numBallots) / numCandidates

}

trait STVTieBreaker {
  def breakTie(candidates: Set[Candidate]): TieBreakResult
}

trait TieBreakResult {
  def loser: Candidate
}

case class BordaTieBreakResult(scores: Map[Candidate, Int], orderedCandidates: Seq[Candidate]) extends TieBreakResult {
  lazy val loser = orderedCandidates.reverse.head
}

case class BordaSTVTieBreaker(election: SingleTransferableVoteElection, ballots: Seq[RankedBallot]) extends STVTieBreaker {
  private lazy val bordaScores =
      new BordaCountElection(election.candidates, election.numPositions).countBallots(ballots).scores

  def breakTie(candidates: Set[Candidate]): BordaTieBreakResult = {
    val orderedCandidates = candidates.map(c => c -> bordaScores(c)).toSeq.sortBy(-1 * _._2).map(_._1)
    BordaTieBreakResult(bordaScores, orderedCandidates)
  }
}

case class RankedPairsTieBreakResult(electionResult: RankedPairsElectionResult) extends TieBreakResult {
  lazy val loser: Candidate = electionResult.orderedCandidates.head
}

case class RankedPairsSTVTieBreaker(ballots: Seq[RankedBallot]) extends STVTieBreaker {
  def breakTie(candidates: Set[Candidate]): RankedPairsTieBreakResult = {
    val reversedBallots = ballots.map(b => new RankedBallot(b.ranking))
    val tieBreakerElection = new RankedBallotRankedPairsElection(candidates, reverse = true)
    RankedPairsTieBreakResult(tieBreakerElection.countBallots(reversedBallots))
  }
}

trait STVEliminator {
  def selectLoser(
                   firstPlaceVoteCountByCandidate: Map[Candidate, BigDecimal],
                   ballots: Seq[RankedBallot],
                   eligibleCandidates: Set[Candidate]
                 ): (Option[Candidate], Option[TieBreakResult])
}

case class FirstPlaceVotesEliminator(tieBreakerFactory: Seq[RankedBallot] => STVTieBreaker) extends STVEliminator {
  def selectLoser(
                   firstPlaceVoteCountByCandidate: Map[Candidate, BigDecimal],
                   ballots: Seq[RankedBallot],
                   eligibleCandidates: Set[Candidate]
                 ): (Option[Candidate], Option[TieBreakResult]) = {

    val lowestVoteCount = firstPlaceVoteCountByCandidate.filterKeys(eligibleCandidates.contains).values.min
    val losers = eligibleCandidates.foldLeft(Set.empty[Candidate])((losers, candidate) => {
      if (firstPlaceVoteCountByCandidate(candidate) == lowestVoteCount) {
        losers + candidate
      } else {
        losers
      }
    })
    if (losers.size == 1) {
      (Some(losers.head), None)
    } else {
      val tieBreaker = tieBreakerFactory(ballots)
      val tieBreakResult = tieBreaker.breakTie(losers)
      (Some(tieBreakResult.loser), Some(tieBreakResult))
    }
  }
}

case class RankedPairsEliminator(candidates: Set[Candidate]) extends STVEliminator {
  val eliminatorElection = new RankedBallotRankedPairsElection(candidates, reverse = true)
  var maybeResult: Option[RankedPairsElectionResult] = None
  def selectLoser(
                   firstPlaceVoteCountByCandidate: Map[Candidate, BigDecimal],
                   ballots: Seq[RankedBallot],
                   eligibleCandidates: Set[Candidate]
                 ): (Option[Candidate], Option[TieBreakResult]) = {
    if (maybeResult.isEmpty) {
      val result = eliminatorElection.countBallots(ballots)
      maybeResult = Some(result)
    }
    val result = maybeResult.get
    val loser = result.orderedCandidates.filter(eligibleCandidates.contains).head
    (Some(loser), None)
  }
}

class SingleTransferableVoteElection(
                                      val candidates: Set[Candidate],
                                      val numPositions: Int,
                                      val quota: (Int, Int) => BigDecimal = SingleTransferableVoteElection.DroopQuota,
                                      val diversityRequirements: DiversityRequirements = DiversityRequirements.none,
                                      val elimination: STVEliminator = FirstPlaceVotesEliminator(RankedPairsSTVTieBreaker.apply)
                                    ) extends Election[RankedBallot, SingleTransferableVoteElectionResult] {

  def countBallots(ballots: Seq[RankedBallot]): SingleTransferableVoteElectionResult = {
    val votesToGetElected = quota(ballots.size, numPositions)

    val initialWeightedBallots = ballots.map(b => WeightedRankedBallot(b,1,b.ranking))

    new SingleTransferableVoteElectionResult(
      recurseRound(
        initialWeightedBallots,
        votesToGetElected,
        numPositions,
        candidates,
        Set.empty
      ),
      votesToGetElected
    )
  }

  def recurseRound(
                    ballots: Seq[WeightedRankedBallot],
                    quota: BigDecimal,
                    numPositionsRemaining: Int,
                    remainingCandidates: Set[Candidate],
                    electedCandidates: Set[Candidate]
                  ): List[STVRoundResult] = {
    if (numPositionsRemaining == 0 || ballots.isEmpty)
      Nil
    else {
      val zeroTally = remainingCandidates.map(c => c -> BigDecimal(0)).toMap
      val firstPlaceVoteCountByCandidate: Map[Candidate, BigDecimal] =
        ballots.foldLeft(zeroTally)((tally, ballot) => {
          if(ballot.remainingVote.isEmpty) {
            tally
          } else {
            val candidateVotedFor = ballot.remainingVote.head
            tally.updated(candidateVotedFor, tally(candidateVotedFor) + ballot.weight)
          }
        }).mapValues(count => count.setScale(6, RoundingMode.HALF_UP))

      val winners: Set[Candidate] = if (remainingCandidates.size <= numPositionsRemaining) {
        remainingCandidates
      } else {
        var diversityExcluded = Set.empty[Candidate]
        firstPlaceVoteCountByCandidate.toSeq
          .sortBy(-1 * _._2) // most votes first, so that candidates with the most votes get the first shot.
          .foldLeft(Set.empty[Candidate])(
          (winners, p) => {
            val (candidate, count) = p
            val newWinners = if (count >= quota && !diversityExcluded.contains(candidate)) {
              winners + candidate
            } else {
              winners
            }

            // Make sure we don't break the diversity requirements if electing multiple candidates at once.
            diversityExcluded = diversityRequirements.excludedCandidates(
              numPositionsRemaining - newWinners.size,
              electedCandidates ++ newWinners,
              remainingCandidates -- newWinners
            )

            newWinners
          }
        )
      }

      val diversityProtectedCandidates = diversityRequirements.protectedCandidates(
        numPositionsRemaining,
        electedCandidates ++ winners,
        remainingCandidates -- winners
      )

      val (loser, tieBreakResult): (Option[Candidate], Option[TieBreakResult]) = if (winners.isEmpty) {
        elimination.selectLoser(
          firstPlaceVoteCountByCandidate,
          ballots.map(_.originalBallot),
          remainingCandidates -- diversityProtectedCandidates
        )
      } else (None, None)

      val newElectedCandidates = electedCandidates ++ winners

      val diversityExcludedCandidates = diversityRequirements.excludedCandidates(
          numPositionsRemaining,
          newElectedCandidates,
          remainingCandidates -- winners -- loser)

      val eliminatedCandidates = winners ++ loser ++ diversityExcludedCandidates

      if (eliminatedCandidates == remainingCandidates) {
        STVRoundResult(
          firstPlaceVoteCountByCandidate,
          exhaustedBallotWeight = 0,
          winners,
          loser,
          diversityProtectedCandidates,
          diversityExcludedCandidates,
          tieBreakResult
        ) :: Nil
      } else if (eliminatedCandidates.nonEmpty) {
        val winnerQuotaFactors: Map[Candidate, BigDecimal] =
            firstPlaceVoteCountByCandidate.foldLeft(Map.empty[Candidate, BigDecimal])((factors, p) => {
              val (candidate, count) = p
              if (winners.contains(candidate)) {
                factors.updated(candidate, (count - quota) / count)
              } else factors
          })

        var exhaustedBallotWeight = BigDecimal(0)
        val newWeightedBallots: Seq[WeightedRankedBallot] = ballots.flatMap(b => {
          if(b.remainingVote.isEmpty) {
            exhaustedBallotWeight += b.weight

            None
          } else {
            Some(
              WeightedRankedBallot(
                b.originalBallot,
                winnerQuotaFactors.getOrElse(b.remainingVote.head, BigDecimal(1)) * b.weight,
                b.remainingVote.filterNot(eliminatedCandidates.contains)
              )
            )
          }
        })

        STVRoundResult(
          firstPlaceVoteCountByCandidate,
          exhaustedBallotWeight,
          winners,
          loser,
          diversityProtectedCandidates,
          diversityExcludedCandidates,
          tieBreakResult
        ) ::
          recurseRound(
            newWeightedBallots,
            quota,
            numPositionsRemaining - winners.size,
            remainingCandidates -- eliminatedCandidates,
            newElectedCandidates
          )
      } else {
        Nil
      }
    }
  }
}