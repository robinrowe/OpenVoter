# every-election-system
This is an election calculator with the goal of being able to run a wide variety of voting systems
and producing detailed results.

#### Current status

Still under development. It is in a usable state, but not complete.

There is a web application that is an election calculation service. `sbt run` will yield a web server
on port 8080, with one endpoint, `/election`, that accepts a `POST` of a JSON document including an
election definition and a list of ballots, and returning a result. See `SerializationSpec.scala`
for example JSON.

#### Future work

* (De)serialization coverage for all implemented elections is the most glaring need. Currently only
  STV and RRV have coverage.
* More thorough unit testing.
* More voting systems.

Other aspects like a system for actually voting and saving ballots and displaying results have
been moved out of the scope of this project, at least for now.