<?php

	class XMLNode Extends XMLElement {

		protected $name;
		protected $attributes;
		protected $children = array();

		public function __construct($name, $attributes=array()) {
			$this->name = $name;
			$this->attributes = $attributes;
		}

		/**
		 * 
		 * @param XMLElement $el
		 * @return XMLElement 
		 */
		public function addChild($el) {
			if ($el) {
				$this->children[] = $el;
			}
			return $el;
		}

		/**
		 *
		 * @param int $depth
		 * @return string 
		 */
		public function asXML($depth = 0) {
			$indent = str_repeat('  ', $depth);
			switch (sizeof($this->children)) {
				case 0: return $indent . HTMLUtils::tag($this->name, null, $this->attributes) . "\r\n";
				case 1: $singleLine = ($this->children[0] instanceof XMLTextElement);
					break;
				default: $singleLine = false;
			}
			$xml = $indent . HTMLUtils::openTag($this->name, $this->attributes) . ($singleLine ? '' : "\r\n");
			foreach ($this->children as $child) {
				if (!is_object($child)){
					var_export($child);
					die();
				}
				$xml.= $child->asXML(($singleLine ? 0 : $depth + 1));
			}
			$xml.= ($singleLine ? '' : $indent) . '</' . $this->name . '>' . "\r\n";
			return $xml;
		}

		/**
		 *
		 * @return string 
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 *
		 * @return string 
		 */
		public function getValue() {
			$value = '';
			foreach ($this->children as $child) {
				$value.= $child->getValue();
			}
			return $value;
		}

		/**
		 *
		 * @return XMLElement[] 
		 */
		public function getChildren() {
			return $this->children;
		}

		/**
		 *
		 * @return string[] 
		 */
		public function getAttributes() {
			return $this->attributes;
		}

		/**
		 *
		 * @param string $name
		 * @return XMLElement 
		 */
		public function firstChildByName($name) {
			foreach ($this->children as $child) {
				if ($child->getName() == $name) {
					return $child;
				}
			}
			return null;
		}

	}

?>