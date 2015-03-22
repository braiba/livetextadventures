<?php

	abstract class XMLElement {

		public abstract function getName();

		public abstract function getValue();

		public abstract function asXML($depth = 0);

		public function __toString() {
			return $this->asXML();
		}

	}

?>