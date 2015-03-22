<?php

	/**
	 * Description of FeedItem
	 *
	 * @author thomas
	 */
	class FeedItem {
		
		protected $title = null;
		protected $created = null;
		protected $modified = null;
		protected $author_email = null;
		protected $author_name = null;
		protected $content = null;
		protected $id = null;
		protected $link = null;
		protected $comments_link = null;
		protected $categories = array();
		
		public function __construct($title, $content, $link = null) {
			$this->title = $title;
			$this->content = $content;
			$this->link = $link;
		}
		
		/**
		 * Get the title
		 * @return string 
		 */
		public function getTitle($fallback = 'Untitled Post'){
			return $this->title ?: $fallback;
		}
		
		/**
		 * Get the post's content
		 * @return string 
		 */
		public function getContent($fallback = 'Empty Post'){
			return $this->content ?: $this->getTitle($fallback);
		}
		
		/**
		 * Get the creation timestamp
		 * @return int 
		 */
		public function getCreated(){
			return $this->created ?: time();
		}
		
		/**
		 * Get the last modified timestamp
		 * @return int 
		 */
		public function getModified(){
			return $this->modified ?: $this->getCreated();
		}
		
		/**
		 * Get the author's email address
		 * @return string 
		 */
		public function getAuthorName($fallback = 'Anonymous'){
			return $this->author_name ?: $fallback;
		}
		
		/**
		 * Get the author's email address
		 * @return string 
		 */
		public function getAuthorEmail($fallback = 'noone@no-email.co.uk'){
			return $this->author_email ?: $fallback;
		}
				
		/**
		 * Get the UID for the post
		 * @return string 
		 */
		public function getID(){
			return $this->id ?: $this->getLink();
		}
		
		/**
		 * Get the link to the post
		 * @return string 
		 */
		public function getLink(){
			return $this->link ?: Framework::linkTo('',true);
		}
		
		/**
		 * Get the link to the post's comments
		 * @return string 
		 */
		public function getCommentsLink(){
			return $this->comments_link ?: $this->getLink().'#comments';
		}
		
		/**
		 * Get the link to the post's categories
		 * @return string[] 
		 */
		public function getCategories(){
			return $this->categories;
		}
		
		public function addCategory($category){
			$this->categories[] = $category;
		}
		
		public function setAuthor($name,$email){
			$this->author_name = $name;
			$this->author_email = $email;
		}
		
		public function setCreated($timestamp){
			$this->created = $timestamp;
		}
		
		public function setModified($timestamp){
			$this->modified = $timestamp;
		}

	}

?>
