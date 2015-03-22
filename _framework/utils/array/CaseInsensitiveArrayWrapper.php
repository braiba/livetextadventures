<?php

	/**
	 *
	 * Wraps the array to make it act as an array with case-insensitive keys.
	 * For example, the keys 'HELLO WORLD' and 'Hello World' would reference the same value.
	 *
	 * @author Thomas
	 */
	class CaseInsensitiveArrayWrapper extends ArrayWrapper {

		public function makeQuickName($name) {
			return strtolower($name);
		}

		/**
		 * Returns an CaseInsensitiveArrayWrapper for the given data if possible.
		 * @param mixed $data The data to be wrapped.
		 * @return CaseInsensitiveArrayWrapper The wrapped data.
		 */
		public static function wrap($data) {
			if (is_array($data))
				return new CaseInsensitiveArrayWrapper($data);
			if ($data instanceof CaseInsensitiveArrayWrapper)
				return $data;
			if ($data instanceof ArrayWrapper)
				return new CaseInsensitiveArrayWrapper($data->getWrappedArray());
			user_error('Object of type ' . gettype($data) . ' is not a valid target for wrapping. Must be an array or ArrayWrapper.');
			return null;
		}

	}

?>