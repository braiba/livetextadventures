<?php

	Framework::useLibrary('xml');
	
	abstract class Feed extends XMLDocument {

		protected $name;
		protected $items = array();

		public function __construct($name, $items=array()) {
			$this->name = $name;
			$this->items = $items;
		}

		public abstract function getDateFormat();
		public abstract function build();
		
		public function addItem(FeedItem $item){
			$this->items[] = $item;
		}

		public function buildTextNode($name, $text, $attributes=array()) {
			if (!$text){
				return null;
			}
			$node = new XMLNode($name, $attributes);
			$node->addChild(new XMLTextElement($text));
			return $node;
		}

		public function buildDateNode($name, $timestamp, $attributes=array()) {
			return $this->buildTextNode($name, date($this->getDateFormat(), $timestamp), $attributes);
		}

	}

?>