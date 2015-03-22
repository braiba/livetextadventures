<?php

	class XMLDocument {

		protected $stack = array();
		protected $root = null;

		protected function setRootNode($root) {
			$this->root = $root;
		}

		protected function startElement($parser, $name, $attrs) {
			$element = new XMLNode($name, $attrs);
			if (is_null($this->root)) {
				$this->root = $element;
			}
			else {
				$parent = end($this->stack);
				$parent->addChild($element);
			}
			$this->stack[] = $element;
		}

		protected function fillElement($parser, $data) {
			$data = trim($data);
			if (empty($data))
				return;
			$parent = end($this->stack);
			$parent->addChild(new XMLTextElement($data));
		}

		protected function endElement($parser, $name) {
			array_pop($this->stack);
		}

		public function openFile($filename) {
			$xml_parser = xml_parser_create();
			xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, array($this, 'startElement'), array($this, 'endElement'));
			xml_set_character_data_handler($xml_parser, array($this, 'fillElement'));
			if (!($fp = fopen($filename, "r"))) {
				die("could not open XML input");
			}

			while ($data = fread($fp, 4096)) {
				if (!xml_parse($xml_parser, $data, feof($fp))) {
					die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
				}
			}
			xml_parser_free($xml_parser);
		}

		public function readString($xml) {
			$xml_parser = xml_parser_create();
			xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, array($this, 'startElement'), array($this, 'endElement'));
			xml_set_character_data_handler($xml_parser, array($this, 'fillElement'));
			xml_parse($xml_parser, $xml, true);
			xml_parser_free($xml_parser);
		}

		public function __toString() {
			return '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\r\n" . $this->root->__toString();
		}

		public function getRoot() {
			return $this->root;
		}

	}

?>