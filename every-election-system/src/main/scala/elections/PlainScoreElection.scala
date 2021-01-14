package elections

import scala.collection.mutable

case class PlainScoreElectionResult(scores: Map[Candidate, BigDecimal]) extends ElectionResult

class PlainScoreElection(val candidates: Set[Candidate]) extends Election[ScoreBallot, PlainScoreElectionResult] {
  override def countBallots(ballots: Seq[ScoreBallot]): PlainScoreElectionResult = {
    val tally = mutable.Map.empty[Candidate, BigDecimal].withDefaultValue(BigDecimal(0))
    ballots.foreach(ballot => {
      candidates.foreach(candidate => {
        tally(candidate) += ballot.normalizedScores.getOrElse(candidate, BigDecimal(0))
      })
    })
    PlainScoreElectionResult(tally.toMap)
  }
}
