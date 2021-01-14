package elections

import akka.actor.ActorSystem
import akka.stream.{ActorMaterializer, Materializer}
import akka.http.scaladsl.Http
import akka.http.scaladsl.server.Directives._
import elections.Serialization._
import de.heikoseeberger.akkahttpplayjson.PlayJsonSupport._

import scala.concurrent.ExecutionContext

object Application {
  implicit val actorSystem: ActorSystem = ActorSystem("election-actor-system")
  implicit val actorMaterializer: ActorMaterializer = ActorMaterializer()
  implicit val executionContext: ExecutionContext = actorSystem.dispatcher

  def main(args: Array[String]): Unit = {
    println("Access the user interface at http://localhost:8080/ ")
    Http().bindAndHandle(route, "localhost", 8080)
  }

  def route(implicit mat: Materializer) = {
    post {
      path("election") {
        entity(as[RunElection]) {
          case RunSTVElection(election, ballots) =>
            println("Counting an STV election with " +
              election.numPositions + " seats, " +
              election.candidates.size + " candidates, and " +
              ballots.size + " ballots."
            )
            val result = election.countBallots(ballots)
            println("Count complete.")
            complete(result)
          case RunRRVElection(election, ballots) =>
            val result = election.countBallots(ballots)
            complete(result)
        }
      }
    } ~
    get {
      path("") {
        getFromResource("index.html")
      }
    }
  }
}
