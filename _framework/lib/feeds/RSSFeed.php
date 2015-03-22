<?php

	class RSSFeed extends Feed {

		protected $description;
		protected $ttl = 30;
				
		public function __construct($name, $description, $items=array()) {
			parent::__construct($name, $items);
			$this->description = $description;
		}

		public function getFilename() {
			return str_replace(' ', '', strtolower($this->name)) . '.rss';
		}

		public function getDateFormat() {
			return 'D, d M Y H:i:s O';
		}
		
		protected function buildItemNode(FeedItem $item) {
			$node = new XMLNode('item');
			$node->addChild($this->buildTextNode('title', $item->getTitle()));
			$node->addChild($this->buildDateNode('pubDate', $item->getCreated()));
			$node->addChild($this->buildTextNode('author', $item->getAuthorEmail() . ' (' . $item->getAuthorName() . ')'));
			$node->addChild($this->buildTextNode('link', $item->getLink()));
			$node->addChild($this->buildTextNode('guid', $item->getID()));
			$node->addChild($this->buildTextNode('comments', $item->getCommentsLink()));
			foreach ($item->getCategories() as $category) {
				$node->addChild($this->buildTextNode('category', $category));
			}
			$node->addChild($this->buildTextNode('description', $item->getContent()));
			return $node;
		}
		
		public function build(){			
			$root = new XMLNode('rss', array('version' => '2.0', 'xmlns:atom' => 'http://www.w3.org/2005/Atom'));
			$base_node = $root->addChild(new XMLNode('channel'));
			$base_node->addChild($this->buildTextNode('generator', 'Postabargain Bespoke Framework'));
			$base_node->addChild($this->buildTextNode('title', $this->name));
			$base_node->addChild($this->buildTextNode('link', Framework::linkTo('', true)));
			$base_node->addChild(new XMLNode('atom:link', array('rel' => 'self', 'type' => 'application/rss+xml', 'href' => 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'])));
			$base_node->addChild($this->buildDateNode('lastBuildDate', time()));
			$base_node->addChild($this->buildTextNode('description', $this->description));
			$base_node->addChild($this->buildTextNode('ttl', $this->ttl));
			foreach ($this->items as $item){
				$base_node->addChild($this->buildItemNode($item));
			}
			return $root;
		}

	}

?>