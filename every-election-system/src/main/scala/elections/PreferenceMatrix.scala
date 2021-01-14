package elections

import scala.collection.mutable
import scalax.collection.Graph
import scalax.collection.edge._

/*
 pairwisePreferences(alice, bob): the number of ballots ranking alice over bob, or equivalent
 */
class PreferenceMatrix(val candidates: Set[Candidate], val pairwisePreferences: Map[(Candidate, Candidate), Double]) {

  private lazy val candidatePairs = for(
    x <- candidates;
    y <- candidates if x != y
  ) yield (x,y)

  lazy val directionalPreferences: Iterable[Preference[Candidate]] = candidatePairs.flatMap(p => {
    val strength = pairwisePreferences(p)
    val reverseStrength = pairwisePreferences((p._2, p._1))
    if (strength > reverseStrength) {
      Some(Preference(p._1, p._2, strength - reverseStrength))
    } else {
      None
    }
  })

  lazy val allPreferences: Iterable[Preference[Candidate]] =
    pairwisePreferences.map(p => Preference(p._1._1, p._1._2, p._2))

  lazy val graph: Graph[Candidate, Preference] = Graph.from(candidates, directionalPreferences)

  lazy val graphIncludingTies: Graph[Candidate, Preference] = Graph.from(candidates, nonNegativePreferences)

  lazy val nonNegativePreferences: Set[Preference[Candidate]] = for(
    x <- candidates;
    y <- candidates if x != y && pairwisePreferences((x, y)) >= pairwisePreferences((y, x))
  ) yield Preference(x,y, pairwisePreferences(x,y) - pairwisePreferences(y,x))

  lazy val preferencesSmallestFirst: Seq[Preference[Candidate]] = directionalPreferences.toSeq.sortBy(_.strength)

  lazy val preferencesLargestFirst: Seq[Preference[Candidate]] = preferencesSmallestFirst.reverse

  lazy val schwartzSet: Set[Candidate] = PreferenceMatrix.calculateSchwartzSet(graph)

  lazy val description: String = {
    pairwisePreferences.seq.toList.sortBy(-1 * _._2).map { case ((x, y), numVoters) =>
      numVoters.toString + " prefer " + x.name + " over " + y.name
    }.mkString("\n")
  }
}


case class Preference[T](yes: T, no: T, strength: Double)
  extends WDiEdge[T]((no, yes), strength) {

  override lazy val toString: String = yes.toString + " over " + no.toString + " at " + strength
  override def equals(other: Any) = other match {
    case Preference(y, n, s) => y == yes && n == no && s == strength
    case _ => false
  }
}

object PreferenceMatrix {
  def fromRankedBallots(candidates: Set[Candidate], ballots: Seq[RankedBallot], reverse: Boolean = false): PreferenceMatrix = {
    val tally = mutable.Map.empty[(Candidate, Candidate), Int].withDefaultValue(0)

    ballots.foreach(ballot => {
      candidates.foreach(x => {candidates.foreach(y => {
        if(x != y) {
          // the preferred candidate is the one that appears first in the ranking.
          // If neither candidate appears, do nothing
          val preferredCandidate: Option[Candidate] = ballot.ranking.find(candidate => candidate == x || candidate == y)
          preferredCandidate.foreach(c => {
            if(c == x) {
              if(reverse) {
                tally((y, x)) += 1
              } else {
                tally((x, y)) += 1
              }
            }
          })
        }
      })})
    })

    new PreferenceMatrix(candidates, tally.toMap.map {
      case (candidatePair: (Candidate, Candidate), strength: Int) => candidatePair -> strength.toDouble
    }.withDefaultValue(0))
  }

  val normalizedScoresToPreferenceUsingDifference: (Option[Double], Option[Double]) => Double =
    (xScore, yScore) => {
      (xScore, yScore) match {
          case (Some(scoreX), Some(scoreY)) if scoreX > scoreY => scoreX - scoreY
          case _                            => 0
        }
    }

  val normalizedScoresToPreferenceIntegral: (Option[Double], Option[Double]) => Double =
    (xScore, yScore) => {
      (xScore, yScore) match {
          case (Some(scoreX), Some(scoreY)) if scoreX > scoreY => 1
          case _                                               => 0
        }
    }



  def fromScoreBallots(candidates: Set[Candidate], ballots: Seq[ScoreBallot]): PreferenceMatrix = {
    val tally = mutable.Map.empty[(Candidate, Candidate), Double].withDefaultValue(0)

    // TODO: Allow this to be changed
    val preferenceCalc: (Option[Double], Option[Double]) => Double =
      normalizedScoresToPreferenceUsingDifference

    ballots.foreach(ballot => {
      candidates.foreach(x => {candidates.foreach(y => {
        if(x != y) {
          tally(x,y) += preferenceCalc(
            ballot.normalizedScores.get(x).map(_.doubleValue()),
            ballot.normalizedScores.get(y).map(_.doubleValue()))
        }
      })})
    })


    new PreferenceMatrix(candidates, tally.toMap.map {
      case (candidatePair: (Candidate, Candidate), strength: Double) => candidatePair -> strength.toDouble
    }.withDefaultValue(0))
  }

  // Floyd-Warshall isn't the most efficient algorithm for this but it is easy to implement
  def calculateSchwartzSet(preferenceGraph: Graph[Candidate, Preference]): Set[Candidate] = {
    val candidates = preferenceGraph.nodes.map(_.value).toSet
    val schwartzSet = for(
      x <- candidates;
      y <- candidates
      if preferenceGraph.get(x).pathTo(preferenceGraph.get(y)).isEmpty &&
        preferenceGraph.get(y).pathTo(preferenceGraph.get(x)).nonEmpty
    ) yield x
    if(schwartzSet.nonEmpty)
      schwartzSet
    else
      candidates
  }

}