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
			else {
				$response = "INVALID_EVENT";
			}


			return $response;
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
					 ->setType($distance)
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
					"distance" => $r["r"]->getType(),
					"id" => $r["r"]->getId(),
				);
				$data[] = $elem;
			}
			
			
			return $data;
		}
	}