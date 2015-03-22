<?php

	class ATOMFeed extends Feed {
		
		public function getDateFormat() {
			return 'Y-m-d\TH:i:s+0I:00';
		}
		
		protected function buildItemNode(FeedItem $item) {
			$node = new XMLNode('entry');
			$node->addChild($this->buildTextNode('title', $item->getTitle()));
			$node->addChild($this->buildTextNode('id', $item->getID()));
			$node->addChild($this->buildDateNode('published', $item->getCreated()));
			$node->addChild($this->buildDateNode('updated', $item->getModified()));
			$author = $node->addChild(new XMLNode('author'));
			$author->addChild($this->buildTextNode('name', $item->getAuthorName()));
			$author->addChild($this->buildTextNode('email', $item->getAuthorEmail()));
			$node->addChild(new XMLNode('link', array('rel' => 'alternate', 'type' => 'text/html', 'href' => $item->getLink())));
			$node->addChild($this->buildTextNode('link', $item->getLink()));
			$node->addChild($this->buildTextNode('comments', $item->getCommentsLink()));
			foreach ($item->getCategories() as $category) {
				$node->addChild(new XMLNode('category', array('term' => $category)));
			}
			$node->addChild($this->buildTextNode('content', $item->getContent(), array('type' => 'html')));
			return $node;
		}
		
		public function build(){
			$root = new XMLNode('feed', array('xmlns' => 'http://www.w3.org/2005/Atom', 'xmlns:idx' => 'urn:atom-extension:indexing', 'idx:index' => 'no'));
			$root->addChild($this->buildTextNode('title', $this->name));
			$root->addChild(new XMLNode('link', array('rel' => 'alternate', 'type' => 'text/html', 'href' => Framework::linkTo('', true))));
			$root->addChild(new XMLNode('link', array('rel' => 'self', 'href' => Framework::linkTo(Framework::getPage(),true))));
			$root->addChild($this->buildTextNode('id', Framework::linkTo('', true)));
			$root->addChild($this->buildDateNode('updated', time()));
			foreach ($this->items as $item){
				$root->addChild($this->buildItemNode($item));
			}
			return $root;
		}

	}

?>