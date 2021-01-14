package elections

class PluralityElectionResult(val numVotesByCandidate: Map[Candidate, Int]) extends ElectionResult {
  lazy val winner: Candidate = numVotesByCandidate.maxBy(_._2)._1
}

class PluralityElection(val candidates: Set[Candidate]) extends Election[SingleVoteBallot, PluralityElectionResult] {
  def countBallots(ballots: Seq[SingleVoteBallot]): PluralityElectionResult = {
    val numVotesByCandidate: Map[Candidate, Int] = {
      ballots.foldLeft(Map.empty[Candidate, Int].withDefaultValue(0))(
        (tallySoFar: Map[Candidate, Int], ballot: SingleVoteBallot) => {
          tallySoFar.updated(ballot.vote, tallySoFar(ballot.vote) + 1)
        })
    }
    new PluralityElectionResult(numVotesByCandidate)
  }
}

