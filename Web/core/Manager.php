<?php
	session_start();

	// Prevent multiple require_once. The following tries to make an automatic require_once based upon it's class name
	spl_autoload_register(function ($sClass) {
		$sLibPath = __DIR__.'/lib/';
		$sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
		$sClassPath = $sLibPath.$sClassFile;
		if (file_exists($sClassPath)) {
			require($sClassPath);
		}
	});

	class Manager {
		private $neo4jClient;
	
		public function __construct() {
			// ======================================================
			// Neo4j methods
			// Connecting to the default port 7474 on localhost
			$this->neo4jClient = new Everyman\Neo4j\Client('localhost', 7474);
		}
		
		public function digest() {
			$response = array();
			$type = $_POST["type"];

			if ($type === "ADD") {
				$node = $this->getNode($_POST["name"]);

				if ($node == null) {
					$node = $this->createNode($_POST["name"], $_POST["x"], $_POST["y"]);
					$response[] = "ADDED";
					$response[] = array(
						"name" => $_POST["name"], 
						"x" => $_POST["x"], 
						"y" => $_POST["y"]
					);
				}
			}
			else if ($type === "ADD_LINK") {
				$node1 = $this->getNode($_POST["name1"]);
				$node2 = $this->getNode($_POST["name2"]);

				if ($node1 != null && $node2 != null) {
					$rel = $this->addRelationship($node1, $_POST["distance"], $node2);
					$response[] = "LINK_ADDED";
				}
			}
			else if ($type === "REMOVE") {
				$response[] = "REMOVED";
				$node = $this->getNode($_POST["name"]);

				if ($node != null) {
					$this->deleteNode($node);
				}
			}
			else if ($type === "REMOVE_LINK") {
				$response[] = "REMOVED";
				$rel = $this->getRelationship($_POST["id"]);

				if ($rel != null) {
					$this->deleteRelationship($rel);
				}
			}
			else if ($type === "REFRESH") {
				$response[] = "REFRESHED";
				$response[] = $this->getRelationships();
				$response[] = $this->getNodes();
			}
			else if ($type === "CALCULATE_DISTANCE") {
				$response[] = "CALCULATION_DONE";
				$tmp = $this->getPath($_POST["name1"], $_POST["name2"]);
				$response[] = $tmp[0];
				$response[] = $tmp[1];

			}
			else if ($type === "REINITIALIZE") {
				$response[] = "REINITIALIZED";
				$this->deleteEverything();

				$mtl = $this->createNode("Montréal", 232, 946);
				$qc = $this->createNode("Québec", 332, 865);
				$tr = $this->createNode("Trois-Rivières", 283, 892);
				$sag = $this->createNode("Saguenay", 340, 800);
				$gat = $this->createNode("Gatineau", 149, 934);
				$gas = $this->createNode("Gaspé", 605, 741);
				$rim = $this->createNode("Rimouski", 464, 768);
				$jer = $this->createNode("St-Jérôme", 214, 889);
				$lau = $this->createNode("Mont-Laurier", 173, 860);
				$she = $this->createNode("Sherbrooke", 314, 942);

				$this->addRelationship($mtl, 141, $tr);
				$this->addRelationship($qc, 126, $tr);
				$this->addRelationship($mtl, 155, $she);
				$this->addRelationship($qc, 235, $she);
				$this->addRelationship($mtl, 203, $gat);
				$this->addRelationship($mtl, 59, $jer);
				$this->addRelationship($gat, 165, $jer);
				$this->addRelationship($jer, 181, $lau);
				$this->addRelationship($gat, 165, $lau);
				$this->addRelationship($qc, 210, $sag);
				$this->addRelationship($qc, 316, $rim);
				$this->addRelationship($rim, 380, $gas);
			}
			else {
				$response = "INVALID_EVENT";
			}

			return $response;
		}

		private function getPath($city1, $city2) {
			$result = array();
			$result[0] = 0;
			$result[1] = array();

			$paths = $this->getNode($city1)->findPathsTo($this->getNode($city2))
		    ->setAlgorithm(Everyman\Neo4j\PathFinder::AlgoDijkstra)
		    ->setCostProperty('distance')
		    ->setMaxDepth(15)
		    ->getPaths();

		    if (sizeof($paths) > 0) {
		    	$paths[0]->setContext(Everyman\Neo4j\Path::ContextRelationship);

			    foreach ($paths[0] as $j => $rel) {
			    	$result[1][] = $rel->getId();
			    	$result[0] = $result[0] + $rel->getProperty("distance");
			    }
			}

			return $result;
		}

		private function deleteEverything() {			
			$query = new Everyman\Neo4j\Cypher\Query($this->neo4jClient, "MATCH (n:City)-[r]->(p:City) DELETE r");
			$result = $query->getResultSet();
			$query = new Everyman\Neo4j\Cypher\Query($this->neo4jClient, "MATCH (n:City) DELETE n");
			$result = $query->getResultSet();
		}

		private function createNode($name, $x, $y) {
			$node = $this->neo4jClient->makeNode();
			$node->setProperty('name', $name);
			$node->setProperty('x', $x);
			$node->setProperty('y', $y);
			$node->save();
			
			$conceptLabel = $this->neo4jClient->makeLabel('City');
			$node->addLabels(array($conceptLabel));
			$node->save();
			
			return $node;
		}

		private function getNode($name) {		
			$queryString = "MATCH (n:City)
							WHERE n.name = {name}
							RETURN n";
							
			$query = new Everyman\Neo4j\Cypher\Query($this->neo4jClient, $queryString, array("name" => $name));
			$result = $query->getResultSet();

			$node = null;

			foreach ($result as $r) {
				$node = $r["n"];
			}

			return $node;
		}

		private function getNodes() {
			$queryString = "MATCH (n:City)
							RETURN n";
							
			$query = new Everyman\Neo4j\Cypher\Query($this->neo4jClient, $queryString);
			$result = $query->getResultSet();

			$data = array();

			foreach ($result as $r) {
				$elem = array(
					"name" => $r["n"]->getProperty("name"),
					"x" => $r["n"]->getProperty("x"),
					"y" => $r["n"]->getProperty("y"),
				);
				$data[] = $elem;
			}

			return $data;
		}

		private function deleteNode($node) {
			$relations = $node->getRelationships();
			
			foreach ($relations as $relation) {
				$relation->delete();
			}
			
			$node->delete();
		}

		private function addRelationship($node1, $distance, $node2) {
			$relation = $this->neo4jClient->makeRelationship();
			$relation->setStartNode($node1)
					 ->setEndNode($node2)
					 ->setType("PATH")
					 ->setProperty("distance", $distance)
					 ->save();
		}

		private function getRelationship($id) {
			$rel = null;

			// somehow I can't bind an id here...
			if (is_numeric($id)) {
				$queryString = "MATCH (n:City)-[r]->(p:City)
								WHERE ID(r) = " . $id . "
								RETURN r";
				$query = new Everyman\Neo4j\Cypher\Query($this->neo4jClient, $queryString);

				$result = $query->getResultSet();
				
				
				if (sizeof($result) > 0) {
					$rel = $result[0]['r'];
				}
			}
			
			return $rel;
		}

		private function deleteRelationship($rel) {
			$rel->delete();
		}

		private function getRelationships() {
			$queryString = "MATCH (n:City)-[r]->(p:City) RETURN n,r,p";
			$query = new Everyman\Neo4j\Cypher\Query($this->neo4jClient, $queryString);
			$result = $query->getResultSet();
			
			$data = array();
			
			foreach ($result as $r) {
				$elem = array(
					"city1_x" => $r["n"]->getProperty("x"),
					"city1_y" => $r["n"]->getProperty("y"),
					"city2_x" => $r["p"]->getProperty("x"),
					"city2_y" => $r["p"]->getProperty("y"),
					"distance" => $r["r"]->getProperty("distance"),
					"id" => $r["r"]->getId(),
				);
				$data[] = $elem;
			}
			
			
			return $data;
		}
	}