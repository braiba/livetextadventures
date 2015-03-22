<?php

	/**
	 * Description of ArrayTable
	 *
	 * @author Thomas
	 */
	class ArrayTable extends AbstractTable {

		protected $columnLabels = array();
		protected $columnData = array();
		protected $data = array();

		public function __construct($columns, $data=array()) {
			if (!is_associative_array($columns)) {
				$columns = array_combine($columns, $columns);
			}
			$this->columnLabels = array_keys($columns);
			$this->columnData = array_values($columns);
			foreach ($data as $row) {
				$this->data[] = SQLQueryResultRow::wrap($row);
			}
		}

		public function getTableClass() {
			return parent::getTableClass() . ' arraytable';
		}

		public function getHeadSize() {
			return 1;
		}

		public function getFootSize() {
			return 0;
		}

		public function hasRowLabels() {
			return false;
		}

		public function rowCount() {
			return sizeof($this->data) + 1;
		}

		public function colCount() {
			return sizeof($this->columnLabels);
		}

		public function getCell($x, $y) {
			if ($y == 0) {
				return $this->columnLabels[$x];
			}
			$value = $this->data[$y - 1][$this->columnData[$x]];
			return (!is_null($value) || $value == '' ? $value : '&nbsp;' );
		}

		public function addRow($data) {
			$this->data[] = $data;
			return true;
		}

	}

?>
