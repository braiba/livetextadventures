<?php

	interface FeedObject {

		public function feed_getTitle();

		public function feed_getAuthorName();

		public function feed_getAuthorEmail();

		public function feed_getLink();

		public function feed_getPublishDate();

		public function feed_getUpdateDate();

		public function feed_getCommentsLink();

		public function feed_getCategories();

		public function feed_getContent();
	}

?>