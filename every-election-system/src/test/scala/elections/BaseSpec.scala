package elections

import org.scalatest.{Matchers, FlatSpec}


abstract class BaseSpec extends FlatSpec with Matchers {
  val alice = Candidate("Alice")
  val bob = Candidate("Bob")
  val carol = Candidate("Carol")
  val david = Candidate("David")
  val emily = Candidate("Emily")
  val frank = Candidate("Frank")
  val grace = Candidate("Grace")
  val homer = Candidate("Homer")
  val irene = Candidate("Irene")
  val jack = Candidate("Jack")
  val kelly = Candidate("Kelly")
  val louis = Candidate("Louis")
  val marge = Candidate("Marge")
  val nancy = Candidate("Nancy")
  val oscar = Candidate("Oscar")
  val peggy = Candidate("Peggy")
  val quinn = Candidate("Quinn")
  val rachel = Candidate("Rachel")
  val steve = Candidate("Steve")
  val tamara = Candidate("Tamara")
  val ulric = Candidate("Ulric")
  val violet = Candidate("Violet")
  val will = Candidate("Will")
  val xandra = Candidate("Xandra")
  val yves = Candidate("Yves")
  val zoe = Candidate("Zoe")
}
