<?php

	class XMLTextElement {

		protected $text;

		public function __construct($text) {
			$this->text = htmlspecialchars($text);
		}

		public function getName() {
			return '[text]';
		}

		public function getValue() {
			return $this->text;
		}

		public function asXML($depth=0) {
			$indent = str_repeat('  ', $depth);
			return $indent . wordwrap($this->text, 256 - $depth * 2, "\r\n" . $indent);
		}

	}

?>