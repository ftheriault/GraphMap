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
			else if ($type === "REMOVE") {
				$response[] = "REMOVED";
				$node = $this->getNode($_POST["name"]);

				if ($node != null) {
					$this->deleteNode($node);
				}
			}
			else if ($type === "REFRESH") {
				$response[] = "REFRESHED";
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
	}