package elections

import scala.util.Random

case class RandomSelectionResult(candidateOrder: Seq[Candidate]) extends ElectionResult {
  lazy val winner = candidateOrder.head
}

class RandomSelection(val candidates: Set[Candidate]) extends Election[Ballot, RandomSelectionResult] {
  def countBallots(ballots: Seq[Ballot]): RandomSelectionResult = {
    RandomSelectionResult(Random.shuffle(candidates.toSeq))
  }
}
