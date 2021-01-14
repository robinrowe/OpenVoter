package elections

import scala.collection.mutable

case class BordaCountElectionResult(scores: Map[Candidate, Int]) extends ElectionResult

object BordaCountElection {
  val plainBorda: Int => Int => Int = numSeats => rank => {
    if(numSeats > rank)
      numSeats - rank
    else
      0
  }
}

class BordaCountElection(
                          val candidates: Set[Candidate],
                          val numSeats: Int,
                          val bordaType: Int => Int => Int = BordaCountElection.plainBorda
                        ) extends Election[RankedBallot, BordaCountElectionResult] {
  def countBallots(ballots: Seq[RankedBallot]): BordaCountElectionResult = {
    val scoreFromRank = bordaType(numSeats)
    val tally = mutable.Map.empty[Candidate, Int].withDefaultValue(0)
    ballots.foreach(ballot => {
      val ballotScores = ballot.ranking.zipWithIndex.map {
        case (candidate, rank) => candidate -> scoreFromRank(rank)
      }.toMap
      candidates.foreach(candidate => {
        tally(candidate) += ballotScores.getOrElse(candidate, 0)
      })
    })

    BordaCountElectionResult(tally.toMap)
  }
}
