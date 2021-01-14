package elections

class RRVElectionResult(val rounds: Seq[RRVElectionRoundResult]) extends ElectionResult {
  lazy val winners = rounds.map(_.winner)
}

case class RRVElectionRoundResult(scores: Map[Candidate, BigDecimal], diversityExcluded: Set[Candidate]) {
  // FIXME: handle ties
  lazy val winner: Candidate = scores.maxBy(_._2)._1
}

object ReweightedRangeVoteElection {

  // No vote for a candidate is the same as marking them 0
  val sumScoreAggregation: (BigDecimal, BigDecimal) => BigDecimal =
    (sumScore: BigDecimal, sumWeight: BigDecimal) => sumScore

  // No vote for a candidate does not affect their score, but a candidate could get elected with a tiny fraction of the vote
  val averageScoreAggregation: (BigDecimal, BigDecimal) => BigDecimal =
    (sumScore: BigDecimal, sumWeight: BigDecimal) => sumScore / sumWeight

  // Compromise. Scoring a candidate zero negatively affects them more than not voting for them at all
  val rootWeightScoreAggregation: (BigDecimal, BigDecimal) => BigDecimal =
    (sumScore: BigDecimal, sumWeight: BigDecimal) => sumScore / math.sqrt(sumWeight.doubleValue())

  val defaultScoreAggregation: (BigDecimal, BigDecimal) => BigDecimal = sumScoreAggregation
  val defaultWeightConstant: BigDecimal = 1.0
}


class ReweightedRangeVoteElection(
                                   val candidates: Set[Candidate],
                                   val numPositions: Int,
                                   val scoreAggregation: (BigDecimal, BigDecimal) => BigDecimal = ReweightedRangeVoteElection.defaultScoreAggregation,
                                   val weightConstant: BigDecimal = ReweightedRangeVoteElection.defaultWeightConstant,
                                   val diversityRequirements: DiversityRequirements = DiversityRequirements.none
                                    ) extends Election[ScoreBallot, RRVElectionResult] {

  def countBallots(ballots: Seq[ScoreBallot]): RRVElectionResult = {
    val initialWeightedBallots = ballots.map(b => new WeightedScoreBallot(b, 1.0))
    new RRVElectionResult(recurseRound(initialWeightedBallots, Set.empty, numPositions))
  }

  def recurseRound(ballots: Seq[WeightedScoreBallot], electedCandidates: Set[Candidate], numPositionsLeft: Int): List[RRVElectionRoundResult] = {
    val diversityExcludedCandidates =
      diversityRequirements.excludedCandidates(
        numPositionsLeft,
        electedCandidates,
        candidates -- electedCandidates
      )

    val remainingCandidates = candidates -- electedCandidates -- diversityExcludedCandidates
    if (numPositionsLeft == 0 || remainingCandidates.isEmpty) Nil
    else {
      val roundResults = remainingCandidates.foldLeft(Map.empty[Candidate, BigDecimal])((m, candidate) => {
        val (totalScore: BigDecimal, totalWeight: BigDecimal) = ballots.foldLeft(BigDecimal(0), BigDecimal(0))((tally, wb) => {
          val (tallyScore, tallyWeight) = tally
          wb.ballot.normalizedScores.get(candidate).map(ballotScore =>
            (tallyScore + (wb.weight * ballotScore), tallyWeight + wb.weight)
          ).getOrElse( (tallyScore, tallyWeight) )
        })
        m + (candidate -> scoreAggregation(totalScore, totalWeight))
      })

      val roundResult = RRVElectionRoundResult(roundResults, diversityExcludedCandidates)

      val newElectedCandidates = electedCandidates + roundResult.winner
      val reweightedBallots = weightBallots(ballots.map(_.ballot), newElectedCandidates)

      roundResult :: recurseRound(reweightedBallots, newElectedCandidates, numPositionsLeft - 1)
    }
  }
  def weightBallots(ballots: Seq[ScoreBallot], electedCandidates: Set[Candidate]): Seq[WeightedScoreBallot] = {
    ballots.map(b => {
      val sumOfScoresOfWinners = electedCandidates.foldLeft(BigDecimal(0))((sum, c) => sum + b.normalizedScores.getOrElse(c,BigDecimal(0)))
      val weight = weightConstant / (weightConstant + sumOfScoresOfWinners)
      WeightedScoreBallot(b, weight)
    })
  }
}