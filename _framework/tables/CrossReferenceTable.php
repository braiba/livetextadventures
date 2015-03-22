<?php

	class CrossReferenceTable extends AbstractTable {

		protected $title = null;
		protected $headers = array();
		protected $columnMap = array();
		protected $rowLabels = array();
		protected $footLabels = array();
		protected $data = array();
		protected $footData = array();

		/**
		 *
		 * @param array $headers
		 * @param array $rowLabels
		 * @param array $data
		 */
		public function __construct($headers, $rowLabels=array(), $data=array()) {
			$this->headers = $headers;
			$this->columnMap = array_keys($headers);
			$this->rowLabels = $rowLabels;
			$this->data = $data;
		}

		public function getTableClass() {
			return parent::getTableClass() . ' crossreference';
		}

		/**
		 * 
		 * @param String $label Label for the row
		 * @param Array $data Data for row
		 * @param boolean $addToFoot Indicates whether the row should be added to the foot, rather than the main body of the table
		 * @return boolean true if successful, false otherwise
		 */
		public function addRow($label, $data, $addToFoot=false) {
			if (sizeof($data) != sizeof($this->headers)) {
				Messages::msg("Row '$label' could not be added to the table. Parameter \$data contained " . sizeof($data) . " elements; " . sizeof($this->headers) . " expected (length of header array).", Messages::M_CODE_ERROR);
				return false;
			}
			if ($addToFoot) {
				$this->footLabels[] = $label;
				$this->footData[] = $data;
			}
			else {
				$this->rowLabels[] = $label;
				$this->data[] = $data;
			}
			return true;
		}

		public function getHeadSize() {
			return 1;
		}

		public function getFootSize() {
			return sizeof($this->footData);
		}

		public function hasRowLabels() {
			return true;
		}

		public function rowCount() {
			return sizeof($this->rowLabels) + sizeof($this->footLabels) + 1;
		}

		public function colCount() {
			return sizeof($this->headers) + 1;
		}

		protected function cellOpen($x, $y) {
			$cell = parent::cellOpen($x, $y);
			if ($x == 0 && $y == 0) {
				return "<$this->currCellType class=\"title_cell\">";
			}
			return $cell;
		}

		public function getCell($x, $y) {
			if ($x == 0) {
				if ($y == 0)
					return (is_null($this->title) ? '&nbsp;' : $this->title);
				if ($y <= sizeof($this->rowLabels))
					return $this->rowLabels[$y - 1];
				return $this->footLabels[$y - sizeof($this->rowLabels) - 1];
			}
			if ($y == 0)
				return $this->headers[$this->columnMap[$x - 1]];
			if ($y - 1 < sizeof($this->data))
				return $this->data[$y - 1][$this->columnMap[$x - 1]];
			return $this->footData[$y - sizeof($this->data) - 1][$this->columnMap[$x - 1]];
		}

		public function getTitle() {
			return $this->title;
		}

		public function setTitle($title) {
			$this->title = $title;
		}

	}

?>